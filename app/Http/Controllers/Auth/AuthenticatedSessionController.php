<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\AccountAlreadyActiveException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActivityLoggerService;
use App\Services\SingleDeviceSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly ActivityLoggerService $activityLogger,
        private readonly SingleDeviceSessionService $singleDeviceSession,
    ) {}

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();
        } catch (ValidationException $e) {
            $this->activityLogger->loginFailed(
                __('Authentication failed for :email', ['email' => $request->input('email')]),
                ['email' => $request->input('email'), 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()],
            );

            throw $e;
        }

        $request->session()->regenerate();

        try {
            $this->singleDeviceSession->activate(
                Auth::user(),
                $request->session()->getId(),
                $request->cookie('installation_id'),
            );
        } catch (AccountAlreadyActiveException $e) {
            $this->activityLogger->loginFailed(
                __('Login blocked for :email, account already active on another device', ['email' => $request->input('email')]),
                ['email' => $request->input('email'), 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()],
            );

            throw ValidationException::withMessages([
                'email' => __('Akun sedang aktif di perangkat lain. Silakan logout dari perangkat aktif terlebih dahulu.'),
            ]);
        }

        $this->activityLogger->loginSuccess(
            __('User :name logged in successfully', ['name' => Auth::user()?->name ?? 'Unknown']),
            ['ip_address' => request()->ip(), 'user_agent' => request()->userAgent()],
        );

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->singleDeviceSession->deactivate(Auth::user());

        $this->activityLogger->logout(
            __('User :name logged out', ['name' => Auth::user()?->name ?? 'Unknown']),
            ['ip_address' => request()->ip(), 'user_agent' => request()->userAgent()],
        );

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
