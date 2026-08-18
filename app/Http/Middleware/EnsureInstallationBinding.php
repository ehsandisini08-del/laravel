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

        if ($user instanceof Model && $user->active_installation_id) {
            $installationId = $request->cookie('installation_id');

            if ($installationId !== $user->active_installation_id) {
                return $this->invalidateSession($request, $user, $guard);
            }
        }

        return $next($request);
    }

    private function invalidateSession(Request $request, Model $user, string $guard): Response
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

        $loginRoute = $guard === 'customer' ? 'portal.login' : 'login';

        return redirect()->route($loginRoute)
            ->with('status', 'Sesi tidak valid. Silakan login kembali.');
    }
}
