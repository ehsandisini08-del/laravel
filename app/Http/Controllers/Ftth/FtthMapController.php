<?php

namespace App\Http\Controllers\Ftth;

use App\Enums\CustomerStatus;
use App\Enums\ServiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Odc;
use App\Models\Odp;
use App\Services\CustomerService;

class FtthMapController extends Controller
{
    public function __construct(
        protected CustomerService $customerService,
    ) {}

    public function index()
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

        $stats = [
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
        ];

        $odcs = Odc::all(['id', 'kode', 'nama']);

        return view('ftth.map.index', compact('stats', 'odcs'));
    }
}
