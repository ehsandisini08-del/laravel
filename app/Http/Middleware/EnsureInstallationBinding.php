<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstallationBinding
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $guard = 'web'): Response
    {
        $user = $request->user($guard);

        if ($user instanceof Model) {
            if ($user->active_installation_id && $user->active_installation_id !== $request->cookie('installation_id')) {
                return $this->invalidateInstallation($request, $user, $guard);
            }

            if ($user->active_session_id && $user->active_session_id !== $request->session()->getId()) {
                return $this->kickInactiveSession($request, $guard);
            }
        }

        return $next($request);
    }

    private function invalidateInstallation(Request $request, Model $user, string $guard): Response
    {
        if ($user->active_session_id && $user->active_session_id !== $request->session()->getId()) {
            DB::table('sessions')->where('id', $user->active_session_id)->delete();
        }

        $user->forceFill([
            'active_session_id' => null,
            'active_installation_id' => null,
        ])->save();

        Auth::guard($guard)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->redirectToLogin($request, $guard, 'Sesi tidak valid. Silakan login kembali.');
    }

    private function kickInactiveSession(Request $request, string $guard): Response
    {
        Auth::guard($guard)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->redirectToLogin($request, $guard, 'Akun sedang aktif di perangkat lain.');
    }

    private function redirectToLogin(Request $request, string $guard, string $message): Response
    {
        $loginRoute = $guard === 'customer' ? 'portal.login' : 'login';

        return redirect()->route($loginRoute)->with('status', $message);
    }
}
