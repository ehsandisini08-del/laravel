<?php

namespace App\Services;

use App\Enums\CustomerStatus;
use App\Enums\ServiceStatus;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PppSecret;
use App\Models\Router;
use App\Models\Setting;
use App\Services\Mikrotik\PPPSecretService as MikrotikPPPSecretService;
use App\Support\SettingSupport;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CustomerService
{
    public function __construct(
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function getAll(array $filters = [])
    {
        $query = Customer::with(['area', 'router', 'package', 'pppSecret']);

        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('customer_code', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('ppp_username', 'like', "%{$s}%");
            });
        }

        if (! empty($filters['area_id'])) {
            $query->where('area_id', $filters['area_id']);
        }

        if (! empty($filters['router_id'])) {
            $query->where('router_id', $filters['router_id']);
        }

        if (! empty($filters['package_id'])) {
            $query->where('package_id', $filters['package_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate(SettingSupport::perPage())->withQueryString();
    }

    public function create(array $data): Customer
    {
        $createPppSecret = ! empty($data['create_ppp_secret']);
        unset($data['create_ppp_secret']);

        if (empty($data['customer_code'])) {
            $data['customer_code'] = $this->generateCustomerCode();
        }

        if (empty($data['status'])) {
            $data['status'] = CustomerStatus::Active->value;
        }

        if (empty($data['due_day'])) {
            $defaultDueDay = (int) Setting::get('default_due_day', '');
            $data['due_day'] = $defaultDueDay >= 1 && $defaultDueDay <= 31 ? $defaultDueDay : null;
        }

        if (empty($data['isolation_day'])) {
            $defaultIsolationDay = Setting::get('default_isolation_day', '');
            $data['isolation_day'] = $defaultIsolationDay === '' ? null : (int) $defaultIsolationDay;
        }

        $portalEnabled = array_key_exists('portal_enabled', $data)
            ? (bool) $data['portal_enabled']
            : true;
        unset($data['portal_enabled']);

        $generatedPassword = null;

        if ($portalEnabled) {
            $generatedPassword = $this->generatePortalPassword();
            $data['portal_password'] = Hash::make($generatedPassword);
            $data['portal_password_plain'] = $generatedPassword;
        }

        $data['portal_enabled'] = $portalEnabled;

        $customer = null;
        $maxAttempts = 5;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                $customer = DB::transaction(function () use ($data, $createPppSecret) {
                    $customer = Customer::create($data);

                    if ($createPppSecret) {
                        $this->createPppSecretForCustomer($customer);
                    }

                    Log::info('Customer created', [
                        'customer_id' => $customer->id,
                        'name' => $customer->name,
                        'ppp_secret_created' => $createPppSecret,
                        'ppp_secret_id' => $customer->ppp_secret_id,
                        'user_id' => auth()->id(),
                    ]);

                    return $customer->load(['area', 'router', 'package', 'pppSecret']);
                });

                break;
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt === $maxAttempts - 1 || ! str_contains($e->getMessage(), 'customers.customer_code')) {
                    throw $e;
                }

                $data['customer_code'] = $this->generateCustomerCode();

                Log::warning('Customer code collision during create, retrying', [
                    'attempt' => $attempt + 1,
                    'user_id' => auth()->id(),
                ]);
            }
        }

        if ($generatedPassword !== null) {
            $customer->generated_portal_password = $generatedPassword;
        }

        return $customer;
    }

    public function generatePortalPassword(): string
    {
        return str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
    }

    public function ensurePortalPassword(Customer $customer): string
    {
        if ($customer->portal_password_plain !== null) {
            return $customer->portal_password_plain;
        }

        $password = $this->generatePortalPassword();

        $customer->update([
            'portal_enabled' => true,
            'portal_password' => Hash::make($password),
            'portal_password_plain' => $password,
        ]);

        Log::info('Portal password generated for customer', [
            'customer_id' => $customer->id,
            'user_id' => auth()->id(),
        ]);

        return $password;
    }

    protected function createPppSecretForCustomer(Customer $customer): void
    {
        if ($customer->ppp_secret_id && PppSecret::where('id', $customer->ppp_secret_id)->exists()) {
            Log::error('createPppSecretForCustomer dipanggil untuk customer yang sudah memiliki PPP Secret', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'existing_ppp_secret_id' => $customer->ppp_secret_id,
            ]);

            throw new \RuntimeException('Customer sudah memiliki PPP Secret. Gunakan update, bukan create.');
        }

        $router = Router::find($customer->router_id);
        if (! $router) {
            throw new \RuntimeException('Router tidak ditemukan.');
        }

        $existing = PppSecret::where('router_id', $router->id)
            ->where('name', $customer->ppp_username)
            ->first();

        if ($existing) {
            $this->syncSecretCommentToRouter($customer, $existing);
            $this->linkExistingPppSecret($customer, $existing);

            return;
        }

        if (! $router->isOnline()) {
            throw new \RuntimeException("Router '{$router->name}' sedang offline. Tidak dapat membuat PPP Secret.");
        }

        $package = Package::with('pppProfile')->find($customer->package_id);
        if (! $package) {
            throw new \RuntimeException('Package tidak ditemukan.');
        }

        $pppProfile = $package->pppProfile;
        if (! $pppProfile) {
            throw new \RuntimeException("Package '{$package->name}' tidak memiliki PPP Profile.");
        }

        $profileName = $pppProfile->name;
        if (empty($profileName)) {
            throw new \RuntimeException('PPP Profile tidak memiliki nama yang valid.');
        }

        $mikrotikService = app()->makeWith(MikrotikPPPSecretService::class, ['router' => $router]);

        $result = $mikrotikService->createSecret([
            'name' => $customer->ppp_username,
            'password' => $customer->ppp_password,
            'profile' => $profileName,
            'service' => 'pppoe',
            'comment' => $customer->name,
        ]);

        if (! $result['success']) {
            $isDuplicate = str_contains($result['message'], 'already exists');

            if ($isDuplicate) {
                Log::info('PPP Secret already exists on router, linking existing secret', [
                    'customer_id' => $customer->id,
                    'router_id' => $router->id,
                    'ppp_username' => $customer->ppp_username,
                ]);

                $found = $mikrotikService->findSecretByName($customer->ppp_username);

                if ($found && ! empty($found['.id'])) {
                    $pppSecret = PppSecret::updateOrCreate(
                        [
                            'router_id' => $router->id,
                            'name' => $found['name'] ?? $customer->ppp_username,
                        ],
                        [
                            'mikrotik_id' => $found['.id'],
                            'password' => $found['password'] ?? $customer->ppp_password,
                            'service' => $found['service'] ?? 'pppoe',
                            'profile' => $found['profile'] ?? $profileName,
                            'local_address' => $found['local-address'] ?? null,
                            'remote_address' => $found['remote-address'] ?? null,
                            'caller_id' => $found['caller-id'] ?? null,
                            'disabled' => isset($found['disabled']) && $found['disabled'] === 'true',
                            'comment' => $customer->name,
                        ]
                    );

                    $this->syncSecretCommentToRouter($customer, $pppSecret);
                    $this->linkExistingPppSecret($customer, $pppSecret);

                    return;
                }
            }

            Log::error('Failed to create PPP Secret on MikroTik', [
                'customer_id' => $customer->id,
                'router_id' => $router->id,
                'ppp_username' => $customer->ppp_username,
                'error' => $result['message'],
            ]);

            throw new \RuntimeException('Gagal membuat PPP Secret di MikroTik: '.$result['message']);
        }

        $responseData = $result['data'] ?? [];
        $mikrotikId = $this->extractMikrotikIdFromResponse($responseData);

        if (! $mikrotikId) {
            $found = $mikrotikService->findSecretByName($customer->ppp_username);

            if ($found && ! empty($found['.id'])) {
                $mikrotikId = $found['.id'];

                Log::info('Resolved MikroTik ID by name after create', [
                    'customer_id' => $customer->id,
                    'ppp_username' => $customer->ppp_username,
                    'mikrotik_id' => $mikrotikId,
                ]);
            } else {
                Log::error('MikroTik response missing .id and could not resolve by name', [
                    'customer_id' => $customer->id,
                    'ppp_username' => $customer->ppp_username,
                    'response' => $responseData,
                ]);

                $mikrotikId = 'auto-'.str()->random(16);
            }
        }

        $pppSecret = PppSecret::create([
            'router_id' => $router->id,
            'mikrotik_id' => $mikrotikId,
            'name' => $customer->ppp_username,
            'password' => $customer->ppp_password,
            'service' => 'pppoe',
            'profile' => $profileName,
            'disabled' => false,
            'comment' => $customer->name,
        ]);

        $customer->update(['ppp_secret_id' => $pppSecret->id]);

        Log::info('PPP Secret created and linked to customer', [
            'customer_id' => $customer->id,
            'ppp_secret_id' => $pppSecret->id,
            'mikrotik_id' => $mikrotikId,
            'router_id' => $router->id,
        ]);
    }

    protected function linkExistingPppSecret(Customer $customer, PppSecret $pppSecret): void
    {
        $data = ['ppp_secret_id' => $pppSecret->id];

        if (! empty($pppSecret->password) && empty($customer->ppp_password)) {
            $data['ppp_password'] = $pppSecret->password;
        }

        $customer->update($data);

        Log::info('Customer linked to existing PPP Secret', [
            'customer_id' => $customer->id,
            'ppp_secret_id' => $pppSecret->id,
            'ppp_username' => $customer->ppp_username,
            'router_id' => $pppSecret->router_id,
            'mikrotik_id' => $pppSecret->mikrotik_id,
            'source' => 'existing',
        ]);
    }

    protected function syncSecretCommentToRouter(Customer $customer, PppSecret $pppSecret): void
    {
        $mikrotikId = $pppSecret->mikrotik_id;

        if (empty($mikrotikId) || str_starts_with($mikrotikId, 'auto-')) {
            Log::warning('PPP Secret tidak memiliki MikroTik ID yang valid, comment tidak disinkronkan', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'ppp_secret_id' => $pppSecret->id,
                'mikrotik_id' => $mikrotikId,
            ]);

            return;
        }

        $router = Router::find($pppSecret->router_id);

        if (! $router || ! $router->isOnline()) {
            Log::warning('Router offline, comment PPP Secret tidak disinkronkan', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'ppp_secret_id' => $pppSecret->id,
                'router_id' => $pppSecret->router_id,
                'mikrotik_id' => $mikrotikId,
            ]);

            return;
        }

        $mikrotikService = app()->makeWith(MikrotikPPPSecretService::class, ['router' => $router]);
        $result = $mikrotikService->updateSecret($mikrotikId, ['comment' => $customer->name]);

        if (! $result['success']) {
            Log::warning('Gagal memperbarui comment PPP Secret di MikroTik', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'ppp_secret_id' => $pppSecret->id,
                'router_id' => $router->id,
                'mikrotik_id' => $mikrotikId,
                'error' => $result['message'],
            ]);

            return;
        }

        $pppSecret->update(['comment' => $customer->name]);

        Log::info('Comment PPP Secret disinkronkan ke MikroTik', [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'ppp_secret_id' => $pppSecret->id,
            'mikrotik_id' => $mikrotikId,
        ]);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $passwordChanged = ! empty($data['ppp_password']);

        if (! $passwordChanged) {
            unset($data['ppp_password']);
        }

        $regeneratePortalPassword = ! empty($data['regenerate_portal_password']);
        $portalEnabled = array_key_exists('portal_enabled', $data)
            ? (bool) $data['portal_enabled']
            : (bool) $customer->portal_enabled;

        unset($data['portal_enabled'], $data['regenerate_portal_password']);

        $generatedPassword = null;

        if ($portalEnabled && ($regeneratePortalPassword || empty($customer->portal_password))) {
            $generatedPassword = $this->generatePortalPassword();
            $data['portal_enabled'] = true;
            $data['portal_password'] = Hash::make($generatedPassword);
            $data['portal_password_plain'] = $generatedPassword;
        } else {
            $data['portal_enabled'] = $portalEnabled;
        }

        $customer->load('pppSecret');

        $customer = DB::transaction(function () use ($customer, $data, $passwordChanged) {
            $originalRouterId = $customer->router_id;
            $originalPackageId = $customer->package_id;
            $originalUsername = $customer->ppp_username;
            $originalCustomerName = $customer->name;

            $customer->update($data);

            if ($customer->ppp_secret_id && ! $customer->relationLoaded('pppSecret')) {
                $customer->load('pppSecret');
            }

            $hasPppSecret = $customer->pppSecret !== null;

            $routerChanged = (int) $customer->router_id !== $originalRouterId;
            $nameChanged = $customer->name !== $originalCustomerName;

            $needsMikrotikSync = $passwordChanged
                || $customer->ppp_username !== $originalUsername
                || $routerChanged
                || (int) $customer->package_id !== $originalPackageId
                || $nameChanged;

            Log::info('Customer update - sync check', [
                'customer_id' => $customer->id,
                'name_changed' => $nameChanged,
                'password_changed' => $passwordChanged,
                'router_changed' => $routerChanged,
                'username_changed' => $customer->ppp_username !== $originalUsername,
                'has_ppp_secret' => $hasPppSecret,
                'ppp_secret_id' => $customer->ppp_secret_id,
                'needs_mikrotik_sync' => $needsMikrotikSync,
                'old_name' => $originalCustomerName,
                'new_name' => $customer->name,
            ]);

            if ($needsMikrotikSync && $hasPppSecret) {
                $this->updatePppSecretOnMikrotik($customer, $passwordChanged, $routerChanged);
            } elseif ($needsMikrotikSync && ! $hasPppSecret && $customer->ppp_secret_id) {
                $customer->load('pppSecret');
                if ($customer->pppSecret) {
                    $this->updatePppSecretOnMikrotik($customer, $passwordChanged, $routerChanged);
                }
            }

            Log::info('Customer updated', [
                'customer_id' => $customer->id,
                'name' => $customer->name,
                'mikrotik_synced' => $needsMikrotikSync && $hasPppSecret,
                'user_id' => auth()->id(),
            ]);

            return $customer->load(['area', 'router', 'package', 'pppSecret']);
        });

        if ($generatedPassword !== null) {
            $customer->generated_portal_password = $generatedPassword;
        }

        return $customer;
    }

    protected function updatePppSecretOnMikrotik(Customer $customer, bool $passwordChanged, bool $routerChanged): void
    {
        $pppSecret = $customer->pppSecret;
        $mikrotikId = $pppSecret->mikrotik_id;
        $oldPppUsername = $pppSecret->name;
        $oldRouterId = $pppSecret->router_id;

        if (empty($mikrotikId)) {
            Log::error('PPP Secret mikrotik_id kosong', [
                'customer_id' => $customer->id,
                'ppp_secret_id' => $pppSecret->id,
            ]);

            throw new \RuntimeException('MikroTik ID PPP Secret kosong. Tidak dapat memperbarui.');
        }

        if ($routerChanged) {
            Log::info('Router berubah, menghapus PPP Secret dari router lama dan membuat di router baru', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'old_router_id' => $pppSecret->router_id,
                'new_router_id' => $customer->router_id,
            ]);

            $oldRouter = Router::find($pppSecret->router_id);
            $newRouter = Router::find($customer->router_id);

            if (! $newRouter) {
                throw new \RuntimeException('Router baru tidak ditemukan.');
            }

            if (! $newRouter->isOnline()) {
                throw new \RuntimeException("Router baru '{$newRouter->name}' sedang offline. Tidak dapat membuat PPP Secret.");
            }

            if ($oldRouter && $oldRouter->isOnline()) {
                $oldMikrotikService = new MikrotikPPPSecretService($oldRouter);
                $deleteResult = $oldMikrotikService->deleteSecret($mikrotikId);

                if (! $deleteResult['success']) {
                    $isNotFound = str_contains($deleteResult['message'], 'not found');

                    if ($isNotFound) {
                        Log::warning('PPP Secret tidak ditemukan di router lama saat migrasi, melanjutkan', [
                            'customer_id' => $customer->id,
                            'old_router_id' => $oldRouter->id,
                            'old_router_name' => $oldRouter->name,
                            'mikrotik_id' => $mikrotikId,
                        ]);
                    } else {
                        Log::error('Gagal menghapus PPP Secret dari router lama saat migrasi', [
                            'customer_id' => $customer->id,
                            'old_router_id' => $oldRouter->id,
                            'old_router_name' => $oldRouter->name,
                            'mikrotik_id' => $mikrotikId,
                            'error' => $deleteResult['message'],
                        ]);

                        throw new \RuntimeException('Gagal menghapus PPP Secret dari router lama: '.$deleteResult['message']);
                    }
                }
            }

            $package = Package::with('pppProfile')->find($customer->package_id);
            if (! $package || ! $package->pppProfile) {
                throw new \RuntimeException('Package atau PPP Profile tidak ditemukan.');
            }

            $newMikrotikService = new MikrotikPPPSecretService($newRouter);

            $createResult = $newMikrotikService->createSecret([
                'name' => $customer->ppp_username,
                'password' => $customer->ppp_password ?: $pppSecret->password,
                'profile' => $package->pppProfile->name,
                'service' => 'pppoe',
                'comment' => $customer->name,
            ]);

            if (! $createResult['success']) {
                Log::error('Gagal membuat PPP Secret di router baru saat migrasi', [
                    'customer_id' => $customer->id,
                    'new_router_id' => $newRouter->id,
                    'new_router_name' => $newRouter->name,
                    'error' => $createResult['message'],
                ]);

                throw new \RuntimeException('Gagal membuat PPP Secret di router baru: '.$createResult['message']);
            }

            $responseData = $createResult['data'] ?? [];
            $newMikrotikId = $this->extractMikrotikIdFromResponse($responseData);

            if (! $newMikrotikId) {
                $newMikrotikId = 'auto-'.str()->random(16);
            }

            $pppSecret->update([
                'router_id' => $newRouter->id,
                'mikrotik_id' => $newMikrotikId,
                'name' => $customer->ppp_username,
                'password' => $customer->ppp_password ?: $pppSecret->password,
                'profile' => $package->pppProfile->name,
                'comment' => $customer->name,
            ]);

            Log::info('PPP Secret migrated to new router', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'ppp_secret_id' => $pppSecret->id,
                'old_router_id' => $oldRouterId,
                'new_router_id' => $newRouter->id,
                'new_mikrotik_id' => $newMikrotikId,
            ]);

            $this->activityLogger->updated('PPP Secret', "PPP Secret migrated to new router for Customer #{$customer->id} ({$customer->name})", $pppSecret, [
                'old_router_id' => $oldRouterId,
                'new_router_id' => $newRouter->id,
                'old_mikrotik_id' => $mikrotikId,
                'new_mikrotik_id' => $newMikrotikId,
            ], $newRouter);

            return;
        }

        $router = Router::find($customer->router_id);
        if (! $router) {
            throw new \RuntimeException('Router tidak ditemukan.');
        }

        if (! $router->isOnline()) {
            throw new \RuntimeException("Router '{$router->name}' sedang offline. Tidak dapat memperbarui PPP Secret.");
        }

        $resolvedId = $this->resolveMikrotikIdFromRouter($router->id, $oldPppUsername);
        if ($resolvedId) {
            $mikrotikId = $resolvedId;
            $pppSecret->update(['mikrotik_id' => $mikrotikId]);

            Log::info('Resolved MikroTik ID by old username before update', [
                'customer_id' => $customer->id,
                'ppp_username' => $oldPppUsername,
                'mikrotik_id' => $mikrotikId,
            ]);
        }

        $package = Package::with('pppProfile')->find($customer->package_id);
        if (! $package || ! $package->pppProfile) {
            throw new \RuntimeException('Package atau PPP Profile tidak ditemukan.');
        }

        $mikrotikService = new MikrotikPPPSecretService($router);

        $updateData = [
            'profile' => $package->pppProfile->name,
            'comment' => $customer->name,
        ];

        if ($passwordChanged) {
            $updateData['password'] = $customer->ppp_password;
        }

        if ($customer->ppp_username !== $pppSecret->name) {
            $updateData['name'] = $customer->ppp_username;
        }

        Log::info('Updating PPP Secret on MikroTik', [
            'customer_id' => $customer->id,
            'mikrotik_id' => $mikrotikId,
            'old_ppp_username' => $oldPppUsername,
            'update_data' => $updateData,
        ]);

        $result = $mikrotikService->updateSecret($mikrotikId, $updateData);

        Log::info('PPP Secret update result', [
            'customer_id' => $customer->id,
            'success' => $result['success'],
            'message' => $result['message'] ?? 'ok',
        ]);

        if (! $result['success']) {
            $isNotFound = str_contains($result['message'], 'not found');

            if ($isNotFound) {
                $retryId = $this->resolveMikrotikIdFromRouter($router->id, $oldPppUsername);

                if ($retryId && $retryId !== $mikrotikId) {
                    Log::warning('Retrying update with different MikroTik ID', [
                        'customer_id' => $customer->id,
                        'old_mikrotik_id' => $mikrotikId,
                        'new_mikrotik_id' => $retryId,
                    ]);

                    $mikrotikId = $retryId;
                    $pppSecret->update(['mikrotik_id' => $mikrotikId]);

                    $retryResult = $mikrotikService->updateSecret($mikrotikId, $updateData);

                    if ($retryResult['success']) {
                        $result = $retryResult;
                    }
                }

                if (! $result['success']) {
                    Log::warning('PPP Secret not found on router, will update local only', [
                        'customer_id' => $customer->id,
                        'router_id' => $router->id,
                        'router_name' => $router->name,
                        'mikrotik_id' => $mikrotikId,
                        'old_ppp_username' => $oldPppUsername,
                    ]);
                }
            } else {
                Log::error('Failed to update PPP Secret on MikroTik', [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'router_id' => $router->id,
                    'router_name' => $router->name,
                    'mikrotik_id' => $mikrotikId,
                    'ppp_username_lama' => $pppSecret->name,
                    'ppp_username_baru' => $customer->ppp_username,
                    'ppp_profile' => $package->pppProfile->name,
                    'error' => $result['message'],
                ]);
            }
        }

        $updatePayload = [
            'name' => $customer->ppp_username,
            'profile' => $package->pppProfile->name,
            'comment' => $customer->name,
        ];

        if ($passwordChanged) {
            $updatePayload['password'] = $customer->ppp_password;
        }

        $pppSecret->update($updatePayload);

        Log::info('PPP Secret updated on MikroTik', [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'ppp_secret_id' => $pppSecret->id,
            'router_id' => $router->id,
            'router_name' => $router->name,
            'mikrotik_id' => $mikrotikId,
            'ppp_username_lama' => $oldPppUsername,
            'ppp_username_baru' => $customer->ppp_username,
            'ppp_profile' => $package->pppProfile->name,
        ]);

        $this->activityLogger->updated('PPP Secret', "PPP Secret updated for Customer #{$customer->id} ({$customer->name})", $pppSecret, [
            'router_id' => $router->id,
            'router_name' => $router->name,
            'mikrotik_id' => $mikrotikId,
            'ppp_username_lama' => $oldPppUsername,
            'ppp_username_baru' => $customer->ppp_username,
            'ppp_profile' => $package->pppProfile->name,
        ], $router);
    }

    public function delete(Customer $customer): bool
    {
        $customer->load('pppSecret');

        return DB::transaction(function () use ($customer) {
            $pppSecret = $customer->pppSecret;

            if ($pppSecret) {
                $this->deletePppSecretFromMikrotik($customer, $pppSecret);
            } elseif ($customer->ppp_secret_id) {
                $customer->load('pppSecret');
                if ($customer->pppSecret) {
                    $this->deletePppSecretFromMikrotik($customer, $customer->pppSecret);
                }
            }

            $customer->delete();

            Log::info('Customer deleted', [
                'customer_id' => $customer->id,
                'name' => $customer->name,
                'user_id' => auth()->id(),
            ]);

            return true;
        });
    }

    protected function deletePppSecretFromMikrotik(Customer $customer, ?PppSecret $pppSecret = null): void
    {
        $pppSecret = $pppSecret ?? $customer->pppSecret;

        $mikrotikId = $pppSecret->mikrotik_id;

        if (empty($mikrotikId)) {
            Log::warning('PPP Secret mikrotik_id kosong, langsung hapus lokal', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'ppp_secret_id' => $pppSecret->id,
            ]);

            $pppSecret->delete();

            $this->activityLogger->deleted('PPP Secret', "PPP Secret #{$pppSecret->id} deleted locally (no mikrotik_id) for Customer #{$customer->id}", null, [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'ppp_secret_id' => $pppSecret->id,
            ]);

            return;
        }

        if (str_starts_with($mikrotikId, 'auto-')) {
            $resolvedId = $this->resolveMikrotikIdFromRouter($pppSecret->router_id, $pppSecret->name);

            if ($resolvedId) {
                $mikrotikId = $resolvedId;

                $pppSecret->update(['mikrotik_id' => $mikrotikId]);

                Log::info('Berhasil meresolusi auto-generated MikroTik ID untuk hapus', [
                    'customer_id' => $customer->id,
                    'ppp_secret_id' => $pppSecret->id,
                    'ppp_username' => $pppSecret->name,
                    'resolved_mikrotik_id' => $mikrotikId,
                ]);
            } else {
                Log::warning('PPP Secret mikrotik_id auto-generated dan tidak ditemukan di Router, hapus lokal', [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'ppp_secret_id' => $pppSecret->id,
                    'mikrotik_id' => $mikrotikId,
                ]);

                $pppSecret->delete();

                $this->activityLogger->deleted('PPP Secret', "PPP Secret #{$pppSecret->id} deleted locally (auto-generated, not found on router) for Customer #{$customer->id}", null, [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'ppp_secret_id' => $pppSecret->id,
                    'mikrotik_id' => $mikrotikId,
                ]);

                return;
            }
        }

        $router = Router::find($pppSecret->router_id);

        if (! $router) {
            Log::warning('Router tidak ditemukan untuk PPP Secret, langsung hapus lokal', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'ppp_secret_id' => $pppSecret->id,
                'router_id' => $pppSecret->router_id,
            ]);

            $pppSecret->delete();

            $this->activityLogger->deleted('PPP Secret', "PPP Secret #{$pppSecret->id} deleted locally (router not found) for Customer #{$customer->id}", null, [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'ppp_secret_id' => $pppSecret->id,
                'router_id' => $pppSecret->router_id,
            ]);

            return;
        }

        if (! $router->isOnline()) {
            Log::error('Router offline, rollback transaksi', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'router_id' => $router->id,
                'router_name' => $router->name,
                'mikrotik_id' => $mikrotikId,
            ]);

            throw new \RuntimeException("Gagal menghapus PPP Secret dari MikroTik karena router '{$router->name}' tidak dapat dihubungi.");
        }

        $mikrotikService = new MikrotikPPPSecretService($router);

        $result = $mikrotikService->deleteSecret($mikrotikId);

        if (! $result['success']) {
            $isNotFound = str_contains($result['message'], 'not found');

            if ($isNotFound) {
                Log::warning('PPP Secret tidak ditemukan di MikroTik, melanjutkan hapus lokal', [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'router_id' => $router->id,
                    'router_name' => $router->name,
                    'mikrotik_id' => $mikrotikId,
                    'ppp_username' => $pppSecret->name,
                    'ppp_profile' => $pppSecret->profile,
                ]);

                $pppSecret->delete();

                $this->activityLogger->deleted('PPP Secret', "PPP Secret #{$pppSecret->id} deleted locally (not found on router) for Customer #{$customer->id}", null, [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'router_id' => $router->id,
                    'router_name' => $router->name,
                    'mikrotik_id' => $mikrotikId,
                    'ppp_username' => $pppSecret->name,
                    'ppp_profile' => $pppSecret->profile,
                ], $router);

                return;
            }

            Log::error('Failed to delete PPP Secret from MikroTik', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'router_id' => $router->id,
                'router_name' => $router->name,
                'mikrotik_id' => $mikrotikId,
                'ppp_username' => $pppSecret->name,
                'ppp_profile' => $pppSecret->profile,
                'error' => $result['message'],
            ]);

            throw new \RuntimeException('Gagal menghapus PPP Secret dari MikroTik: '.$result['message']);
        }

        $pppSecret->delete();

        Log::info('PPP Secret deleted from MikroTik', [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'ppp_secret_id' => $pppSecret->id,
            'router_id' => $router->id,
            'router_name' => $router->name,
            'mikrotik_id' => $mikrotikId,
            'ppp_username' => $pppSecret->name,
            'ppp_profile' => $pppSecret->profile,
        ]);

        $this->activityLogger->deleted('PPP Secret', "PPP Secret #{$pppSecret->id} deleted from router for Customer #{$customer->id} ({$customer->name})", $pppSecret, [
            'router_id' => $router->id,
            'router_name' => $router->name,
            'mikrotik_id' => $mikrotikId,
            'ppp_username' => $pppSecret->name,
            'ppp_profile' => $pppSecret->profile,
        ], $router);
    }

    protected function resolveMikrotikIdFromRouter(int $routerId, string $username): ?string
    {
        $router = Router::find($routerId);

        if (! $router || ! $router->isOnline()) {
            return null;
        }

        $service = new MikrotikPPPSecretService($router);

        $found = $service->findSecretByName($username);

        if ($found && ! empty($found['.id'])) {
            return $found['.id'];
        }

        $allSecrets = $service->getAllSecrets();

        if (empty($allSecrets)) {
            return null;
        }

        foreach ($allSecrets as $secret) {
            if (isset($secret['name']) && $secret['name'] === $username) {
                return $secret['.id'] ?? null;
            }
        }

        return null;
    }

    protected function extractMikrotikIdFromResponse(array $responseData): ?string
    {
        if (empty($responseData)) {
            return null;
        }

        if (isset($responseData[0]) && is_array($responseData[0])) {
            return $responseData[0]['ret'] ?? $responseData[0]['.id'] ?? null;
        }

        if (isset($responseData[0]) && is_string($responseData[0]) && str_starts_with($responseData[0], '*')) {
            return $responseData[0];
        }

        if (isset($responseData['ret'])) {
            return $responseData['ret'];
        }

        if (isset($responseData['.id'])) {
            return $responseData['.id'];
        }

        return null;
    }

    public function generateCustomerCode(): string
    {
        $maxAttempts = 1000;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            if (! Customer::where('customer_code', $code)->exists()) {
                return $code;
            }
        }

        Log::error('Failed to generate unique customer code', [
            'used_codes' => Customer::count(),
        ]);

        throw new \RuntimeException('Tidak dapat membuat kode customer yang unik. Kapasitas kode (999.999) sudah hampir penuh.');
    }

    public function getPackagesByRouter(int $routerId)
    {
        return Package::with('areas')
            ->where('router_id', $routerId)
            ->active()
            ->get(['id', 'name', 'price', 'router_id']);
    }

    public function getAreasByPackage(int $packageId)
    {
        $package = Package::with('areas')->findOrFail($packageId);

        return $package->areas()->active()->get(['id', 'code', 'name']);
    }

    public function getActiveAreas()
    {
        return Area::active()->orderBy('name')->get(['id', 'code', 'name']);
    }

    public function getActiveRouters()
    {
        return Router::enabled()->orderBy('name')->get(['id', 'name']);
    }

    public function reconcileSecrets(?int $routerId = null): array
    {
        $summary = [
            'routers' => [],
            'total' => ['updated' => 0, 'created' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []],
        ];

        if ($routerId) {
            $router = Router::find($routerId);

            if (! $router) {
                $summary['total']['failed'] = 1;
                $summary['total']['errors'][] = "Router #{$routerId} tidak ditemukan.";

                return $summary;
            }

            $routers = collect([$router]);
        } else {
            $routerIds = Customer::query()
                ->where('status', '!=', CustomerStatus::Terminated->value)
                ->whereNotNull('router_id')
                ->distinct()
                ->pluck('router_id');

            $routers = Router::enabled()->whereIn('id', $routerIds)->get();
        }

        foreach ($routers as $router) {
            $result = $this->reconcileSecretsForRouter($router);

            $summary['routers'][$router->name] = $result;

            foreach (['updated', 'created', 'skipped', 'failed'] as $key) {
                $summary['total'][$key] += $result[$key];
            }

            $summary['total']['errors'] = array_merge($summary['total']['errors'], $result['errors']);
        }

        return $summary;
    }

    public function reconcileSecretsForRouter(Router $router): array
    {
        $result = [
            'processed' => 0,
            'updated' => 0,
            'created' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $customers = Customer::with(['package.pppProfile', 'pppSecret'])
            ->where('router_id', $router->id)
            ->where('status', '!=', CustomerStatus::Terminated->value)
            ->get();

        $routerOnline = $router->isOnline();

        foreach ($customers as $customer) {
            $result['processed']++;

            $profileName = $customer->package?->pppProfile?->name;

            if (empty($profileName)) {
                $result['skipped']++;
                $result['errors'][] = "Customer #{$customer->id} ({$customer->name}): tidak memiliki paket dengan PPP Profile.";

                continue;
            }

            $expectedDisabled = $customer->service_status === ServiceStatus::Isolated;

            try {
                if ($customer->pppSecret) {
                    $outcome = $this->reconcileLinkedSecret($customer, $router, $profileName, $expectedDisabled, $routerOnline);
                } else {
                    $outcome = $this->reconcileMissingSecret($customer, $router, $profileName, $expectedDisabled, $routerOnline);
                }

                $result[$outcome['status']]++;

                if (! empty($outcome['error'])) {
                    $result['errors'][] = $outcome['error'];
                }
            } catch (\Throwable $e) {
                $result['failed']++;
                $result['errors'][] = "Customer #{$customer->id} ({$customer->name}): {$e->getMessage()}";
            }
        }

        Log::info('Customer secrets reconciled on router', [
            'router_id' => $router->id,
            'router_name' => $router->name,
            ...$result,
        ]);

        return $result;
    }

    protected function reconcileLinkedSecret(Customer $customer, Router $router, string $profileName, bool $expectedDisabled, bool $routerOnline): array
    {
        $pppSecret = $customer->pppSecret;
        $mikrotikId = $pppSecret->mikrotik_id;

        if (empty($mikrotikId) || str_starts_with($mikrotikId, 'auto-')) {
            $resolvedId = $this->resolveMikrotikIdFromRouter($pppSecret->router_id, $pppSecret->name);

            if ($resolvedId) {
                $mikrotikId = $resolvedId;
                $pppSecret->update(['mikrotik_id' => $mikrotikId]);
            }
        }

        if (empty($mikrotikId)) {
            return ['status' => 'failed', 'error' => "Customer #{$customer->id} ({$customer->name}): MikroTik ID secret tidak dapat ditentukan."];
        }

        if (! $routerOnline) {
            return ['status' => 'failed', 'error' => "Customer #{$customer->id} ({$customer->name}): router '{$router->name}' offline."];
        }

        $updateData = [
            'profile' => $profileName,
            'comment' => $customer->name,
        ];

        if (! empty($customer->ppp_password)) {
            $updateData['password'] = $customer->ppp_password;
        }

        if ($customer->ppp_username !== $pppSecret->name) {
            $updateData['name'] = $customer->ppp_username;
        }

        $mikrotikService = app()->makeWith(MikrotikPPPSecretService::class, ['router' => $router]);
        $result = $mikrotikService->updateSecret($mikrotikId, $updateData);

        if (! $result['success']) {
            return ['status' => 'failed', 'error' => "Customer #{$customer->id} ({$customer->name}): ".$result['message']];
        }

        $localUpdate = [
            'profile' => $profileName,
            'comment' => $customer->name,
            'name' => $customer->ppp_username,
        ];

        if (! empty($customer->ppp_password)) {
            $localUpdate['password'] = $customer->ppp_password;
        }

        if ((bool) $pppSecret->disabled !== $expectedDisabled) {
            $statusResult = $expectedDisabled
                ? $mikrotikService->disableSecret($mikrotikId)
                : $mikrotikService->enableSecret($mikrotikId);

            if (! $statusResult['success']) {
                return ['status' => 'failed', 'error' => "Customer #{$customer->id} ({$customer->name}): gagal mengubah status secret: ".$statusResult['message']];
            }

            $localUpdate['disabled'] = $expectedDisabled;
        }

        $pppSecret->update($localUpdate);

        return ['status' => 'updated', 'error' => null];
    }

    protected function reconcileMissingSecret(Customer $customer, Router $router, string $profileName, bool $expectedDisabled, bool $routerOnline): array
    {
        if (! $routerOnline) {
            return ['status' => 'failed', 'error' => "Customer #{$customer->id} ({$customer->name}): router '{$router->name}' offline."];
        }

        $this->createPppSecretForCustomer($customer);

        $pppSecret = $customer->fresh(['pppSecret'])->pppSecret;

        if ($expectedDisabled && $pppSecret && ! $pppSecret->disabled) {
            $mikrotikService = app()->makeWith(MikrotikPPPSecretService::class, ['router' => $router]);
            $result = $mikrotikService->disableSecret($pppSecret->mikrotik_id);

            if (! $result['success']) {
                return ['status' => 'failed', 'error' => "Customer #{$customer->id} ({$customer->name}): secret dibuat tapi gagal disable: ".$result['message']];
            }

            $pppSecret->update(['disabled' => true]);
        }

        return ['status' => 'created', 'error' => null];
    }
}
