<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\ActivityLoggerService;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerService $customerService,
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index(Request $request)
    {
        $customers = $this->customerService->getAll($request->only(['search', 'area_id', 'router_id', 'package_id', 'status']));
        $areas = $this->customerService->getActiveAreas();
        $routers = $this->customerService->getActiveRouters();

        return view('customers.index', compact('customers', 'areas', 'routers'));
    }

    public function create()
    {
        $areas = $this->customerService->getActiveAreas();
        $routers = $this->customerService->getActiveRouters();

        return view('customers.create', compact('areas', 'routers'));
    }

    public function store(StoreCustomerRequest $request)
    {
        try {
            $customer = $this->customerService->create($request->validated());

            $this->activityLogger->created('Customer', "Customer #{$customer->id} ({$customer->name}) created", $customer);

            return redirect()->route('customers.index')
                ->with('success', 'Customer berhasil ditambahkan.');
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
        $customer->load(['area', 'router', 'package.pppProfile', 'pppSecret']);

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        $customer->load(['area', 'router', 'package', 'pppSecret']);
        $areas = $this->customerService->getActiveAreas();
        $routers = $this->customerService->getActiveRouters();
        $packages = $this->customerService->getPackagesByRouter($customer->router_id);

        return view('customers.edit', compact('customer', 'areas', 'routers', 'packages'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        try {
            $this->customerService->update($customer, $request->validated());

            $this->activityLogger->updated('Customer', "Customer #{$customer->id} ({$customer->name}) updated", $customer);

            return redirect()->route('customers.index')
                ->with('success', 'Customer berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Failed to update customer', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Gagal memperbarui customer: '.$e->getMessage());
        }
    }

    public function destroy(Customer $customer)
    {
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
}
