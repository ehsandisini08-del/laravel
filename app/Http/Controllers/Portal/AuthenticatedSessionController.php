<?php

namespace App\Http\Controllers\Portal;

use App\Exceptions\AccountAlreadyActiveException;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\SingleDeviceSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly SingleDeviceSessionService $singleDeviceSession,
    ) {}

    public function create(): View
    {
        return view('portal.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'customer_code' => ['required', 'string', 'max:6'],
            'password' => ['required', 'string', 'max:3'],
        ]);

        $customer = Customer::where('customer_code', $credentials['customer_code'])->first();

        if (! $customer || ! $customer->portal_enabled || ! Hash::check($credentials['password'], (string) $customer->portal_password)) {
            Log::warning('Portal login failed', [
                'customer_code' => $credentials['customer_code'],
                'ip_address' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'customer_code' => 'Kode customer atau password salah.',
            ]);
        }

        Auth::guard('customer')->login($customer, $request->boolean('remember'));
        $request->session()->regenerate();

        try {
            $this->singleDeviceSession->activate(
                $customer,
                $request->session()->getId(),
                $request->cookie('installation_id'),
            );
        } catch (AccountAlreadyActiveException $e) {
            Log::warning('Portal login blocked, account already active on another device', [
                'customer_id' => $customer->id,
                'customer_code' => $customer->customer_code,
                'ip_address' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'customer_code' => 'Akun sedang aktif di perangkat lain. Silakan logout dari perangkat aktif terlebih dahulu.',
            ]);
        }

        $customer->update(['portal_last_login_at' => now()]);

        Log::info('Portal login success', [
            'customer_id' => $customer->id,
            'customer_code' => $customer->customer_code,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->intended(route('portal.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->singleDeviceSession->deactivate(auth('customer')->user());

        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
