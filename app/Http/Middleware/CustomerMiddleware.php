<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('customer');

        if (! $user) {
            return redirect()->route('portal.login');
        }

        if (! $user instanceof Customer) {
            return redirect()->route('portal.login');
        }

        return $next($request);
    }
}
