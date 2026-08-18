<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Services\ActivityLoggerService;
use App\Services\SingleDeviceSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UnlockAccountController extends Controller
{
    public function __construct(
        private readonly SingleDeviceSessionService $singleDeviceSession,
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index(): View
    {
        $sessionInfo = DB::table('sessions')
            ->whereIn('id', collect(User::whereNotNull('active_session_id')->pluck('active_session_id'))
                ->merge(Customer::whereNotNull('active_session_id')->pluck('active_session_id')))
            ->get()
            ->keyBy('id');

        $users = User::where(fn ($q) => $q->whereNotNull('active_session_id')->orWhereNotNull('active_installation_id'))
            ->latest()
            ->get();

        $customers = Customer::where(fn ($q) => $q->whereNotNull('active_session_id')->orWhereNotNull('active_installation_id'))
            ->latest()
            ->get();

        return view('unlock-accounts.index', compact('users', 'customers', 'sessionInfo'));
    }

    public function unlockUser(User $user): RedirectResponse
    {
        $this->singleDeviceSession->kick($user);

        $this->activityLogger->updated('User', "Session user {$user->name} ({$user->email}) di-unlock", $user);

        return back()->with('success', "Sesi user {$user->name} berhasil di-unlock.");
    }

    public function unlockCustomer(Customer $customer): RedirectResponse
    {
        $this->singleDeviceSession->kick($customer);

        $this->activityLogger->updated('Customer', "Session customer {$customer->name} ({$customer->customer_code}) di-unlock", $customer);

        return back()->with('success', "Sesi customer {$customer->name} berhasil di-unlock.");
    }
}
