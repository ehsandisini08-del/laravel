<?php

namespace App\Services;

use App\Models\Area;
use App\Support\SettingSupport;
use Illuminate\Support\Facades\Log;

class AreaService
{
    public function getAll(array $filters = [])
    {
        $query = Area::query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status'] === 'active');
        }

        if (! empty($filters['area_ids'])) {
            $query->whereIn('id', $filters['area_ids']);
        }

        return $query->latest()->paginate(SettingSupport::perPage())->withQueryString();
    }

    public function findOrFail(int $id): Area
    {
        return Area::findOrFail($id);
    }

    public function create(array $data): Area
    {
        $area = Area::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        Log::info('Area created', [
            'area_id' => $area->id,
            'code' => $area->code,
            'user_id' => auth()->id(),
        ]);

        return $area;
    }

    public function update(Area $area, array $data): Area
    {
        $area->update([
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? $area->is_active,
        ]);

        Log::info('Area updated', [
            'area_id' => $area->id,
            'code' => $area->code,
            'user_id' => auth()->id(),
        ]);

        return $area;
    }

    public function delete(Area $area): bool
    {
        Log::info('Area deleted', [
            'area_id' => $area->id,
            'code' => $area->code,
            'user_id' => auth()->id(),
        ]);

        return $area->delete();
    }

    public function getActiveAreas()
    {
        return Area::active()->orderBy('name')->get(['id', 'code', 'name']);
    }
}
