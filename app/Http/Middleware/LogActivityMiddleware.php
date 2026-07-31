<?php

namespace App\Http\Middleware;

use App\Services\ActivityLoggerService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivityMiddleware
{
    public function __construct(
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        if (! $request->user()) {
            return;
        }

        $methods = ['POST', 'PUT', 'PATCH', 'DELETE'];

        if (! in_array($request->method(), $methods, true)) {
            return;
        }

        $route = $request->route();
        if (! $route || ! $route->getName()) {
            return;
        }

        $name = $route->getName();
        $action = match (true) {
            str_contains($name, '.store') => 'Created',
            str_contains($name, '.update') => 'Updated',
            str_contains($name, '.destroy') => 'Deleted',
            str_contains($name, '.bulk-delete') => 'Bulk Deleted',
            str_contains($name, '.bulk-enable') => 'Bulk Enabled',
            str_contains($name, '.bulk-disable') => 'Bulk Disabled',
            str_contains($name, '.enable') => 'Enabled',
            str_contains($name, '.disable') => 'Disabled',
            str_contains($name, '.sync') => 'Synced',
            str_contains($name, '.test-connection') => 'Test Connection',
            default => null,
        };

        if (! $action) {
            return;
        }

        $module = match (true) {
            str_contains($name, 'routers') => 'Router',
            str_contains($name, 'ppp-secrets') => 'PPP Secret',
            str_contains($name, 'ppp-profiles') => 'PPP Profile',
            str_contains($name, 'ppp-active') => 'Active Connection',
            str_contains($name, 'packages') => 'Package',
            str_contains($name, 'areas') => 'Area',
            default => 'System',
        };

        $description = "{$module} {$action} via {$request->method()} {$request->path()}";

        $this->activityLogger->log(
            module: $module,
            action: $action,
            description: $description,
            properties: [
                'status_code' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
            ],
        );
    }
}
