<?php

namespace App\Services;

use App\Models\User;
use App\Support\SettingSupport;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function getAll(array $filters = [])
    {
        $query = User::query()->with('areas');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        return $query->latest()->paginate(SettingSupport::perPage())->withQueryString();
    }

    public function create(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? User::ROLE_ADMIN_AREA,
        ]);

        $this->syncAreas($user, $data['areas'] ?? []);

        Log::info('User created', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'created_by' => auth()->id(),
        ]);

        return $user;
    }

    public function update(User $user, array $data): User
    {
        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'] ?? $user->role,
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        $this->syncAreas($user, $data['areas'] ?? []);

        Log::info('User updated', [
            'user_id' => $user->id,
            'updated_by' => auth()->id(),
        ]);

        return $user;
    }

    /**
     * @param  array<int, int>  $areaIds
     */
    protected function syncAreas(User $user, array $areaIds): void
    {
        if ($user->isAdminArea()) {
            $user->areas()->sync(array_values(array_map('intval', $areaIds)));
        } else {
            $user->areas()->sync([]);
        }
    }

    public function delete(User $user): bool
    {
        if (auth()->id() === $user->id) {
            throw new \RuntimeException('Tidak dapat menghapus akun sendiri.');
        }

        if ($user->isDeveloper()) {
            $developerCount = User::where('role', User::ROLE_DEVELOPER)->count();

            if ($developerCount <= 1) {
                throw new \RuntimeException('Tidak dapat menghapus developer terakhir.');
            }
        }

        $user->delete();

        Log::info('User deleted', [
            'user_id' => $user->id,
            'deleted_by' => auth()->id(),
        ]);

        return true;
    }
}
