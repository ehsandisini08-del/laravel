<?php

namespace App\Http\Controllers;

use App\Enums\CustomerStatus;
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

        $activeCustomers = (clone $customerQuery)
            ->where('status', CustomerStatus::Active)
            ->count();

        $isolatedCustomers = (clone $customerQuery)
            ->where('status', CustomerStatus::Isolated)
            ->count();

        $inactiveCustomers = (clone $customerQuery)
            ->whereIn('status', [
                CustomerStatus::Suspended,
                CustomerStatus::Terminated,
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
