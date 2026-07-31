<?php

namespace App\Services;

use App\Models\Router;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Contracts\Activity;

class ActivityLoggerService
{
    public function log(
        string $module,
        string $action,
        string $description,
        ?Model $subject = null,
        ?array $properties = [],
        ?Router $router = null,
    ): ?Activity {
        try {
            $causer = Auth::user();

            $defaults = [
                'module' => $module,
                'action' => $action,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
            ];

            if ($router) {
                $defaults['router_id'] = $router->id;
                $defaults['router_name'] = $router->name;
            }

            $log = activity()
                ->causedBy($causer)
                ->withProperties(array_merge($defaults, $properties))
                ->event($action);

            if ($subject instanceof Model) {
                $log->performedOn($subject);
            }

            return $log->log($description);
        } catch (\Throwable $e) {
            Log::error('Activity logging failed', [
                'error' => $e->getMessage(),
                'module' => $module,
                'action' => $action,
                'description' => $description,
            ]);

            return null;
        }
    }

    public function created(string $module, string $description, ?Model $subject = null, ?array $properties = []): ?Activity
    {
        return $this->log($module, 'Created', $description, $subject, $properties);
    }

    public function updated(string $module, string $description, ?Model $subject = null, ?array $properties = []): ?Activity
    {
        return $this->log($module, 'Updated', $description, $subject, $properties);
    }

    public function deleted(string $module, string $description, ?Model $subject = null, ?array $properties = []): ?Activity
    {
        return $this->log($module, 'Deleted', $description, $subject, $properties);
    }

    public function synced(string $module, string $description, ?Router $router = null, ?array $properties = []): ?Activity
    {
        return $this->log($module, 'Synced', $description, null, $properties, $router);
    }

    public function loginSuccess(string $description, ?array $properties = []): ?Activity
    {
        return $this->log('Authentication', 'Login Success', $description, null, $properties);
    }

    public function loginFailed(string $description, ?array $properties = []): ?Activity
    {
        return $this->log('Authentication', 'Login Failed', $description, null, $properties);
    }

    public function logout(string $description, ?array $properties = []): ?Activity
    {
        return $this->log('Authentication', 'Logout', $description, null, $properties);
    }

    public function connected(string $description, ?Router $router = null, ?array $properties = []): ?Activity
    {
        return $this->log('Router', 'Connected', $description, null, $properties, $router);
    }

    public function connectionFailed(string $description, ?Router $router = null, ?array $properties = []): ?Activity
    {
        return $this->log('Router', 'Connection Failed', $description, null, $properties, $router);
    }
}
