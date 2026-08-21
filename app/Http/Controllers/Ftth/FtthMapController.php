<?php

namespace App\Http\Controllers\Ftth;

use App\Enums\CustomerStatus;
use App\Enums\ServiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Odc;
use App\Models\Odp;

class FtthMapController extends Controller
{
    public function index()
    {
        $stats = [
            'total_odc' => Odc::count(),
            'total_odp' => Odp::count(),
            'total_customers' => Customer::count(),
            'customers_online' => Customer::where('status', CustomerStatus::Active->value)
                ->where('service_status', ServiceStatus::Active->value)
                ->count(),
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
