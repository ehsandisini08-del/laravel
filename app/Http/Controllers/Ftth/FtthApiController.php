<?php

namespace App\Http\Controllers\Ftth;

use App\Enums\CustomerStatus;
use App\Enums\ServiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\FiberLine;
use App\Models\Odc;
use App\Models\Odp;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FtthApiController extends Controller
{
    public function __construct(
        protected CustomerService $customerService,
    ) {}

    /**
     * Stats summary untuk dashboard map.
     */
    public function stats(): JsonResponse
    {
        $allCustomers = Customer::whereNotNull('router_id')
            ->whereNotNull('ppp_username')
            ->get(['id', 'router_id', 'ppp_username', 'status', 'service_status']);

        $pppActive = $this->customerService->getPppActiveConnections($allCustomers);

        $onlineCount = 0;
        foreach ($allCustomers as $c) {
            if (isset($pppActive[$c->router_id.':'.$c->ppp_username])) {
                $onlineCount++;
            }
        }

        $totalCustomers = Customer::count();

        return response()->json([
            'total_odc' => Odc::count(),
            'total_odp' => Odp::count(),
            'total_customers' => $totalCustomers,
            'customers_online' => $onlineCount,
            'customers_offline' => max(0, $totalCustomers - $onlineCount),
            'customers_gangguan' => Customer::where('service_status', ServiceStatus::Overdue->value)->count(),
            'customers_isolir' => Customer::where('service_status', ServiceStatus::Isolated->value)->count(),
            'customers_nonaktif' => Customer::whereIn('status', [
                CustomerStatus::Suspended->value,
                CustomerStatus::Terminated->value,
            ])->count(),
        ]);
    }

    /**
     * Daftar semua ODC dengan koordinat.
     */
    public function odcs(): JsonResponse
    {
        $odcs = Odc::select(['id', 'kode', 'nama', 'alamat', 'latitude', 'longitude', 'kapasitas', 'status'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(fn ($odc) => [
                'id' => $odc->id,
                'kode' => $odc->kode,
                'nama' => $odc->nama,
                'alamat' => $odc->alamat,
                'lat' => (float) $odc->latitude,
                'lng' => (float) $odc->longitude,
                'kapasitas' => $odc->kapasitas,
                'status' => $odc->status,
                'odp_count' => Odp::where('odc_id', $odc->id)->count(),
                'url' => route('ftth.odc.show', $odc->id),
            ]);

        return response()->json($odcs);
    }

    /**
     * Daftar semua ODP dengan koordinat.
     */
    public function odps(Request $request): JsonResponse
    {
        $query = Odp::with('odc:id,kode,nama,latitude,longitude')
            ->select(['id', 'odc_id', 'kode', 'nama', 'alamat', 'latitude', 'longitude', 'kapasitas', 'port_terpakai', 'status'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($request->filled('odc_id')) {
            $query->where('odc_id', $request->odc_id);
        }

        $odps = $query->get()->map(fn ($odp) => [
            'id' => $odp->id,
            'kode' => $odp->kode,
            'nama' => $odp->nama,
            'alamat' => $odp->alamat,
            'lat' => (float) $odp->latitude,
            'lng' => (float) $odp->longitude,
            'kapasitas' => $odp->kapasitas,
            'port_terpakai' => $odp->port_terpakai,
            'port_available' => $odp->port_available,
            'status' => $odp->status,
            'odc' => $odp->odc ? [
                'id' => $odp->odc->id,
                'kode' => $odp->odc->kode,
                'nama' => $odp->odc->nama,
                'lat' => (float) $odp->odc->latitude,
                'lng' => (float) $odp->odc->longitude,
            ] : null,
            'url' => route('ftth.odp.show', $odp->id),
        ]);

        return response()->json($odps);
    }

    /**
     * Daftar pelanggan dengan koordinat, status PPP active, dan nilai redaman GenieACS.
     */
    public function customers(Request $request): JsonResponse
    {
        $query = Customer::with([
            'odp:id,kode,nama,latitude,longitude,odc_id',
            'package:id,name',
            'cpes:id,customer_id,ppp_username,model_name,serial_number,status,signal_parameters,last_inform_at',
        ])
            ->select([
                'id',
                'customer_code',
                'name',
                'address',
                'latitude',
                'longitude',
                'status',
                'service_status',
                'router_id',
                'ppp_username',
                'odp_id',
                'port_odp',
                'package_id',
            ])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($request->filled('odp_id')) {
            $query->where('odp_id', $request->odp_id);
        }

        if ($request->filled('odc_id')) {
            $odpIds = Odp::where('odc_id', $request->odc_id)->pluck('id');
            $query->whereIn('odp_id', $odpIds);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $serviceStatusFilter = $request->get('service_status');
        if ($serviceStatusFilter && ! in_array($serviceStatusFilter, ['online', 'offline', 'active'])) {
            $query->where('service_status', $serviceStatusFilter);
        }

        $customers = $query->limit(5000)->get();

        // Ambil lookup koneksi aktif PPP MikroTik (cached 30 detik di CustomerService)
        $pppActive = $this->customerService->getPppActiveConnections($customers);

        $results = $customers->map(function ($c) use ($pppActive) {
            $key = $c->router_id && $c->ppp_username ? $c->router_id.':'.$c->ppp_username : null;
            $activeConn = $key ? ($pppActive[$key] ?? null) : null;
            $isOnline = $activeConn !== null;
            $uptime = $activeConn['uptime'] ?? null;

            $cpe = $c->cpes->first();
            $rxPower = $cpe?->rx_power;

            return [
                'id' => $c->id,
                'customer_code' => $c->customer_code,
                'name' => $c->name,
                'address' => $c->address,
                'lat' => (float) $c->latitude,
                'lng' => (float) $c->longitude,
                'status' => $c->status,
                'service_status' => $c->service_status?->value ?? null,
                'is_online' => $isOnline,
                'uptime' => $uptime,
                'rx_power' => $rxPower,
                'cpe' => $cpe ? [
                    'model' => $cpe->model_name ?? $cpe->genieacs_id,
                    'serial' => $cpe->serial_number,
                    'status' => $cpe->status,
                    'rx_power' => $rxPower,
                    'last_inform' => $cpe->last_inform_at?->format('d/m/Y H:i'),
                ] : null,
                'odp' => $c->odp ? [
                    'id' => $c->odp->id,
                    'kode' => $c->odp->kode,
                    'nama' => $c->odp->nama,
                    'lat' => (float) $c->odp->latitude,
                    'lng' => (float) $c->odp->longitude,
                    'odc_id' => $c->odp->odc_id,
                ] : null,
                'odp_id' => $c->odp_id,
                'port_odp' => $c->port_odp,
                'package' => $c->package ? $c->package->name : null,
                'url' => route('customers.show', $c->id),
            ];
        });

        // Filter status online / offline berdasarkan PPP active
        if ($serviceStatusFilter === 'online' || $serviceStatusFilter === 'active') {
            $results = $results->filter(fn ($item) => $item['is_online'] === true)->values();
        } elseif ($serviceStatusFilter === 'offline') {
            $results = $results->filter(fn ($item) => $item['is_online'] === false)->values();
        }

        return response()->json($results->values());
    }

    /**
     * Jalur fiber dengan koordinat.
     */
    public function fibers(): JsonResponse
    {
        $fibers = FiberLine::where('status', FiberLine::STATUS_ACTIVE)
            ->get()
            ->map(fn ($f) => [
                'id' => $f->id,
                'nama' => $f->nama,
                'tipe_kabel' => $f->tipe_kabel,
                'source_type' => $f->source_type,
                'source_id' => $f->source_id,
                'destination_type' => $f->destination_type,
                'destination_id' => $f->destination_id,
                'coordinates' => $f->leaflet_coords,
                'status' => $f->status,
            ]);

        return response()->json($fibers);
    }

    /**
     * Search ODC, ODP, pelanggan dengan info status & redaman.
     */
    public function search(Request $request): JsonResponse
    {
        $q = $request->get('q', '');
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $results = [];

        $odcs = Odc::where('kode', 'like', "%{$q}%")
            ->orWhere('nama', 'like', "%{$q}%")
            ->whereNotNull('latitude')
            ->limit(5)
            ->get(['id', 'kode', 'nama', 'latitude', 'longitude']);

        foreach ($odcs as $odc) {
            $results[] = [
                'type' => 'odc',
                'id' => $odc->id,
                'label' => "[ODC] {$odc->kode} — {$odc->nama}",
                'lat' => (float) $odc->latitude,
                'lng' => (float) $odc->longitude,
            ];
        }

        $odps = Odp::where('kode', 'like', "%{$q}%")
            ->orWhere('nama', 'like', "%{$q}%")
            ->whereNotNull('latitude')
            ->limit(5)
            ->get(['id', 'kode', 'nama', 'latitude', 'longitude']);

        foreach ($odps as $odp) {
            $results[] = [
                'type' => 'odp',
                'id' => $odp->id,
                'label' => "[ODP] {$odp->kode} — {$odp->nama}",
                'lat' => (float) $odp->latitude,
                'lng' => (float) $odp->longitude,
            ];
        }

        $customers = Customer::with('cpes:id,customer_id,signal_parameters')
            ->where(function ($query) use ($q) {
                $query->where('customer_code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%");
            })
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->limit(8)
            ->get(['id', 'customer_code', 'name', 'router_id', 'ppp_username', 'latitude', 'longitude']);

        $pppActive = $this->customerService->getPppActiveConnections($customers);

        foreach ($customers as $c) {
            $key = $c->router_id && $c->ppp_username ? $c->router_id.':'.$c->ppp_username : null;
            $isOnline = $key && isset($pppActive[$key]);
            $cpe = $c->cpes->first();
            $rxPower = $cpe?->rx_power;
            $rxInfo = $rxPower ? " | RX: {$rxPower}" : '';
            $statusTag = $isOnline ? '🟢 Online' : '⚪ Offline';

            $results[] = [
                'type' => 'customer',
                'id' => $c->id,
                'label' => "[{$statusTag}] {$c->customer_code} — {$c->name}{$rxInfo}",
                'lat' => (float) $c->latitude,
                'lng' => (float) $c->longitude,
            ];
        }

        return response()->json($results);
    }
}
