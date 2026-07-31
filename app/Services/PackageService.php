<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Package;
use App\Models\PppProfile;
use Illuminate\Support\Facades\Log;

class PackageService
{
    public function getAll(array $filters = [])
    {
        $query = Package::with(['router', 'pppProfile', 'areas']);

        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $query->where('name', 'like', "%{$s}%");
        }

        if (! empty($filters['router_id'])) {
            $query->where('router_id', $filters['router_id']);
        }

        if (! empty($filters['area_id'])) {
            $query->whereHas('areas', fn ($q) => $q->where('area_id', $filters['area_id']));
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return $query->latest()->paginate(15)->withQueryString();
    }

    public function findOrFail(int $id): Package
    {
        return Package::with(['router', 'pppProfile', 'areas'])->findOrFail($id);
    }

    public function create(array $data): Package
    {
        $package = Package::create([
            'name' => $data['name'],
            'price' => $data['price'],
            'router_id' => $data['router_id'],
            'ppp_profile_id' => $data['ppp_profile_id'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        if (! empty($data['areas'])) {
            $package->areas()->sync($data['areas']);
        }

        Log::info('Package created', [
            'package_id' => $package->id,
            'name' => $package->name,
            'user_id' => auth()->id(),
        ]);

        return $package;
    }

    public function update(Package $package, array $data): Package
    {
        $package->update([
            'name' => $data['name'],
            'price' => $data['price'],
            'router_id' => $data['router_id'],
            'ppp_profile_id' => $data['ppp_profile_id'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? $package->is_active,
        ]);

        if (isset($data['areas'])) {
            $package->areas()->sync($data['areas']);
        }

        Log::info('Package updated', [
            'package_id' => $package->id,
            'name' => $package->name,
            'user_id' => auth()->id(),
        ]);

        return $package->load(['router', 'pppProfile', 'areas']);
    }

    public function delete(Package $package): bool
    {
        Log::info('Package deleted', [
            'package_id' => $package->id,
            'name' => $package->name,
            'user_id' => auth()->id(),
        ]);

        $package->areas()->detach();

        return $package->delete();
    }

    public function getProfilesByRouter(int $routerId)
    {
        return PppProfile::forRouter($routerId)
            ->synced()
            ->get(['id', 'name']);
    }

    public function getActiveAreas()
    {
        return Area::active()->orderBy('name')->get(['id', 'code', 'name']);
    }
}
