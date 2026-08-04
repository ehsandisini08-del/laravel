<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppNotInMaintenance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('webhooks/*') || $request->is('up')) {
            return $next($request);
        }

        if ($request->is('login') || $request->is('portal/login') || $request->routeIs('password.*')) {
            return $next($request);
        }

        $user = Auth::guard('web')->user();

        $isDeveloper = $user instanceof User && $user->isDeveloper();

        $maintenance = Setting::get('maintenance_mode', '0') === '1';

        Log::info('Maintenance check', [
            'path' => $request->path(),
            'maintenance' => $maintenance,
            'has_user' => $user !== null,
            'is_developer' => $isDeveloper,
            'user_role' => $user?->role,
        ]);

        if ($maintenance && ! $isDeveloper) {
            abort(503, 'Sistem sedang dalam pemeliharaan.');
        }

        return $next($request);
    }
}
