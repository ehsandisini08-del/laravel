<?php

namespace App\Services\WhatsApp;

use App\Models\WaTemplate;
use App\Support\SettingSupport;

class WhatsAppTemplateService
{
    public function getAll(array $filters = [])
    {
        $query = WaTemplate::query();

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('title', 'like', "%{$filters['search']}%");
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->latest()->paginate(SettingSupport::perPage())->withQueryString();
    }

    public function create(array $data): WaTemplate
    {
        return WaTemplate::create($data);
    }

    public function update(WaTemplate $template, array $data): WaTemplate
    {
        $template->update($data);

        return $template;
    }

    public function delete(WaTemplate $template): bool
    {
        return $template->delete();
    }
}
