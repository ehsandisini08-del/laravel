<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Odp;
use App\Models\Setting;
use App\Models\WaDevice;
use App\Services\ActivityLoggerService;
use App\Services\CustomerService;
use App\Services\Excel\CustomerExcelExporter;
use App\Services\Excel\CustomerExcelImporter;
use App\Services\WhatsApp\WhatsAppGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerService $customerService,
        private readonly ActivityLoggerService $activityLogger,
        private readonly WhatsAppGatewayService $whatsApp,
    ) {}

    public function index(Request $request)
    {
        $customers = $this->customerService->getAll($request->only(['search', 'area_id', 'router_id', 'package_id', 'status']));
        $pppActiveConnections = $this->customerService->getPppActiveConnections($customers);

        if ($request->wantsJson()) {
            return view('customers.partials.list', compact('customers', 'pppActiveConnections'));
        }

        $areas = $this->customerService->getActiveAreas();
        $routers = $this->customerService->getActiveRouters();

        return view('customers.index', compact('customers', 'areas', 'routers', 'pppActiveConnections'));
    }

    public function create()
    {
        $this->denyAdminArea();

        $areas = $this->customerService->getActiveAreas();
        $routers = $this->customerService->getActiveRouters();
        $odps = Odp::with('odc')->orderBy('kode')->get();

        return view('customers.create', compact('areas', 'routers', 'odps'));
    }

    public function store(StoreCustomerRequest $request)
    {
        $this->denyAdminArea();

        try {
            $customer = $this->customerService->create($request->validated());

            $this->activityLogger->created('Customer', "Customer #{$customer->id} ({$customer->name}) created", $customer);

            $flash = [
                'success' => 'Customer berhasil ditambahkan.',
            ];

            if ($customer->generated_portal_password !== null) {
                $flash['portal_password'] = $customer->generated_portal_password;
            }

            return redirect()->route('customers.index')->with($flash);
        } catch (\Exception $e) {
            Log::error('Failed to create customer', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withInput()->with('error', 'Gagal menambahkan customer: '.$e->getMessage());
        }
    }

    public function show(Customer $customer)
    {
        $this->authorizeCustomer($customer);

        $customer->load([
            'area',
            'router',
            'package.pppProfile',
            'pppSecret',
            'odp.odc',
            'cpes' => fn ($query) => $query->latest('synced_at'),
        ]);

        $activeBills = $customer->invoices()
            ->whereIn('status', ['unpaid', 'overdue'])
            ->with('package')
            ->orderByDesc('billing_year')
            ->orderByDesc('billing_month')
            ->get();

        $invoiceHistory = $customer->invoices()
            ->with('package')
            ->orderByDesc('billing_year')
            ->orderByDesc('billing_month')
            ->limit(10)
            ->get();

        return view('customers.show', compact('customer', 'activeBills', 'invoiceHistory'));
    }

    public function edit(Customer $customer)
    {
        $this->denyAdminArea();
        $this->denyTeknisi();

        $customer->load(['area', 'router', 'package', 'pppSecret', 'odp.odc']);
        $areas = $this->customerService->getActiveAreas();
        $routers = $this->customerService->getActiveRouters();
        $packages = $this->customerService->getPackagesByRouter($customer->router_id);
        $odps = Odp::with('odc')->orderBy('kode')->get();

        return view('customers.edit', compact('customer', 'areas', 'routers', 'packages', 'odps'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $this->denyAdminArea();
        $this->denyTeknisi();

        try {
            $customer = $this->customerService->update($customer, $request->validated());

            $this->activityLogger->updated('Customer', "Customer #{$customer->id} ({$customer->name}) updated", $customer);

            $flash = [
                'success' => 'Customer berhasil diperbarui.',
            ];

            if ($customer->generated_portal_password !== null) {
                $flash['portal_password'] = $customer->generated_portal_password;
            }

            return redirect()->route('customers.index')->with($flash);
        } catch (\Exception $e) {
            Log::error('Failed to update customer', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Gagal memperbarui customer: '.$e->getMessage());
        }
    }

    public function sendPortalPasswordViaWhatsApp(Customer $customer)
    {
        $this->authorizeCustomer($customer);
        $this->denyTeknisi();

        if (! $customer->portal_enabled) {
            return redirect()->route('customers.show', $customer)
                ->with('error', 'Akses portal pelanggan ini nonaktif. Aktifkan terlebih dahulu.');
        }

        $phone = $this->normalizeWhatsAppPhone($customer->phone);

        if (! $phone) {
            return redirect()->route('customers.show', $customer)
                ->with('error', 'Nomor WhatsApp pelanggan tidak valid.');
        }

        $device = WaDevice::where('status', 'connected')->first();

        if (! $device) {
            return redirect()->route('customers.show', $customer)
                ->with('error', 'Tidak ada perangkat WhatsApp yang terhubung. Pastikan WhatsApp Gateway sudah terhubung.');
        }

        $password = $this->customerService->ensurePortalPassword($customer);

        $message = $this->buildPortalLoginMessage($customer, $password);

        try {
            $waMessage = $this->whatsApp->sendMessage($device, $phone, $message, 'text', $customer->id);
        } catch (\Exception $e) {
            Log::error('Failed to send portal login via WhatsApp', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('customers.show', $customer)
                ->with('error', 'Gagal mengirim pesan: '.$e->getMessage());
        }

        if ($waMessage->status === 'failed') {
            return redirect()->route('customers.show', $customer)
                ->with('error', 'Gagal mengirim pesan ke WhatsApp pelanggan. Periksa status perangkat WhatsApp Gateway.');
        }

        $this->activityLogger->updated('Customer', "Portal login sent via WhatsApp for Customer #{$customer->id} ({$customer->name})", $customer);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Informasi login portal berhasil dikirim via WhatsApp ke '.$customer->phone.'.');
    }

    protected function normalizeWhatsAppPhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $phone = preg_replace('/\D/', '', $phone);

        if (strlen($phone) < 9) {
            return null;
        }

        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        } elseif (! str_starts_with($phone, '62')) {
            $phone = '62'.$phone;
        }

        return $phone;
    }

    protected function buildPortalLoginMessage(Customer $customer, string $password): string
    {
        $company = Setting::get('company_name') ?: (Setting::get('app_name') ?: config('app.name'));

        $appUrl = Setting::get('customer_app_url') ?: url('/portal');

        return implode("\n", [
            "Halo {$customer->name},",
            '',
            'Berikut informasi login akun Portal Pelanggan Anda:',
            '',
            "Portal: {$company}",
            'Download Aplikasi: '.$appUrl,
            "Kode Customer: {$customer->customer_code}",
            "Password: {$password}",
            '',
            'Gunakan kode tersebut untuk melihat tagihan dan riwayat pembayaran Anda.',
        ]);
    }

    public function destroy(Customer $customer)
    {
        $this->denyAdminArea();
        $this->denyTeknisi();

        try {
            $this->customerService->delete($customer);

            $this->activityLogger->deleted('Customer', "Customer #{$customer->id} ({$customer->name}) deleted", $customer);

            return redirect()->route('customers.index')
                ->with('success', 'Customer berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Failed to delete customer', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menghapus customer: '.$e->getMessage());
        }
    }

    public function destroyMany(Request $request)
    {
        abort_unless(auth()->user()->canDeleteCustomers(), 403);

        $ids = collect((array) $request->input('ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->slice(0, 500)
            ->all();

        $customers = Customer::whereIn('id', $ids)->get();

        if ($customers->isEmpty()) {
            return back()->with('error', 'Tidak ada customer yang dipilih untuk dihapus.');
        }

        $deleted = 0;
        $failed = 0;

        foreach ($customers as $customer) {
            try {
                $this->customerService->delete($customer);
                $deleted++;
            } catch (\Exception $e) {
                $failed++;
                Log::error('Failed to delete customer (bulk)', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($deleted > 0) {
            $this->activityLogger->deleted('Customer', "{$deleted} customer dihapus massal", null, [
                'count' => $deleted,
                'ids' => $customers->pluck('id')->all(),
                'deleted_by' => auth()->user()?->name,
            ]);
        }

        $message = "{$deleted} customer berhasil dihapus.";

        if ($failed > 0) {
            $message .= " {$failed} customer gagal dihapus.";
        }

        return back()->with($deleted > 0 ? 'success' : 'error', $message);
    }

    public function packagesByRouter(int $routerId)
    {
        $packages = $this->customerService->getPackagesByRouter($routerId);

        return response()->json($packages);
    }

    public function areasByPackage(int $packageId)
    {
        $areas = $this->customerService->getAreasByPackage($packageId);

        return response()->json($areas);
    }

    public function reconcile(Request $request)
    {
        $this->denyAdminArea();
        $this->denyTeknisi();

        try {
            $result = $this->customerService->reconcileSecrets($request->input('router_id'));

            $total = $result['total'];

            $this->activityLogger->updated(
                'Customer',
                "Customer secrets reconciled: {$total['updated']} updated, {$total['created']} created, {$total['skipped']} skipped, {$total['failed']} failed"
            );

            return response()->json([
                'success' => true,
                'message' => "Sinkronisasi selesai: {$total['updated']} diperbarui, {$total['created']} dibuat, {$total['skipped']} dilewati, {$total['failed']} gagal.",
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reconcile customer secrets', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json(['success' => false, 'message' => 'Gagal sinkronisasi: '.$e->getMessage()], 500);
        }
    }

    public function importForm()
    {
        $this->denyAdminArea();
        $this->denyTeknisi();

        return view('customers.import');
    }

    public function importTemplate(CustomerExcelExporter $exporter)
    {
        $this->denyAdminArea();
        $this->denyTeknisi();

        $spreadsheet = $exporter->createTemplate();

        $response = new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$exporter->fileName().'"');

        return $response;
    }

    public function import(Request $request, CustomerExcelImporter $importer)
    {
        $this->denyAdminArea();
        $this->denyTeknisi();

        Log::info('Customer import started', [
            'file_name' => $request->file('file')?->getClientOriginalName(),
            'file_size' => $request->file('file')?->getSize(),
            'upload_error' => $request->file('file') ? $request->file('file')->getError() : null,
            'user_id' => auth()->id(),
        ]);

        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:10240'],
        ]);

        $linkPppSecret = $request->boolean('link_ppp_secret', true);

        try {
            $result = $importer->import($request->file('file'), $linkPppSecret);
        } catch (\Exception $e) {
            Log::error('Failed to import customers from excel', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->with('error', 'Gagal memproses file: '.$e->getMessage());
        }

        $flash = ['import_result' => $result];

        if ($result['errors']) {
            $flash['warning'] = $result['success'].' customer berhasil diimpor, '.count($result['errors']).' gagal.';
        } else {
            $flash['success'] = $result['success'].' customer berhasil diimpor.';
        }

        Log::info('Customer import completed', [
            'success' => $result['success'],
            'errors' => count($result['errors']),
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('customers.import.form')->with($flash);
    }

    protected function denyAdminArea(): void
    {
        if (auth()->user()->isAdminArea()) {
            abort(403, 'Akses ditolak.');
        }
    }

    protected function denyTeknisi(): void
    {
        if (auth()->user()->isTeknisi()) {
            abort(403, 'Akses ditolak.');
        }
    }

    protected function authorizeCustomer(Customer $customer): void
    {
        if (auth()->user()->isAdminArea() && ! in_array($customer->area_id, auth()->user()->areaIds(), true)) {
            abort(403, 'Akses ditolak.');
        }
    }
}
