<?php

namespace App\Http\Controllers;

use App\Enums\CustomerStatus;
use App\Enums\ServiceStatus;
use App\Models\Customer;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $customerQuery = Customer::query();
        if ($user && $user->isAdminArea()) {
            $customerQuery->whereIn('area_id', $user->areaIds());
        }

        $totalCustomers = $customerQuery->count();

        // Stats
        $newCustomers = (clone $customerQuery)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $isolatedCustomers = (clone $customerQuery)
            ->where(function ($q) {
                $q->where('service_status', ServiceStatus::Isolated->value)
                    ->orWhere('status', CustomerStatus::Isolated->value);
            })
            ->count();

        $activeCustomers = (clone $customerQuery)
            ->where('status', CustomerStatus::Active->value)
            ->where(function ($q) {
                $q->whereNull('service_status')
                    ->orWhere('service_status', '!=', ServiceStatus::Isolated->value);
            })
            ->count();

        $inactiveCustomers = (clone $customerQuery)
            ->whereIn('status', [
                CustomerStatus::Suspended->value,
                CustomerStatus::Terminated->value,
            ])
            ->count();

        return view('dashboard', compact(
            'totalCustomers',
            'newCustomers',
            'activeCustomers',
            'isolatedCustomers',
            'inactiveCustomers'
        ));
    }
}
