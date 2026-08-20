<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Item;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class GudangBarangService
{
    public function getItems(array $filters = [])
    {
        $query = Item::with('category');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return $query->latest()->paginate(15)->withQueryString();
    }

    public function createItem(array $data): Item
    {
        $item = Item::create([
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'category_id' => $data['category_id'] ?? null,
            'unit' => $data['unit'],
            'description' => $data['description'] ?? null,
            'min_stock' => $data['min_stock'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        Log::info('Gudang item created', [
            'item_id' => $item->id,
            'code' => $item->code,
            'user_id' => auth()->id(),
        ]);

        return $item;
    }

    public function updateItem(Item $item, array $data): Item
    {
        $item->update([
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'category_id' => $data['category_id'] ?? null,
            'unit' => $data['unit'],
            'description' => $data['description'] ?? null,
            'min_stock' => $data['min_stock'],
            'is_active' => $data['is_active'] ?? $item->is_active,
        ]);

        Log::info('Gudang item updated', [
            'item_id' => $item->id,
            'code' => $item->code,
            'user_id' => auth()->id(),
        ]);

        return $item;
    }

    public function deleteItem(Item $item): bool
    {
        if (StockMovement::where('item_id', $item->id)->exists()) {
            throw ValidationException::withMessages([
                'item' => "Barang {$item->name} memiliki riwayat pergerakan stok dan tidak dapat dihapus.",
            ]);
        }

        Log::info('Gudang item deleted', [
            'item_id' => $item->id,
            'code' => $item->code,
            'user_id' => auth()->id(),
        ]);

        return $item->delete();
    }

    public function getCategories(array $filters = [])
    {
        $query = Category::withCount('items');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return $query->latest()->paginate(15)->withQueryString();
    }

    public function createCategory(array $data): Category
    {
        $category = Category::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        Log::info('Gudang category created', [
            'category_id' => $category->id,
            'name' => $category->name,
            'user_id' => auth()->id(),
        ]);

        return $category;
    }

    public function updateCategory(Category $category, array $data): Category
    {
        $category->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? $category->is_active,
        ]);

        Log::info('Gudang category updated', [
            'category_id' => $category->id,
            'name' => $category->name,
            'user_id' => auth()->id(),
        ]);

        return $category;
    }

    public function deleteCategory(Category $category): bool
    {
        if ($category->items()->exists()) {
            throw ValidationException::withMessages([
                'category' => "Kategori {$category->name} masih memiliki barang dan tidak dapat dihapus.",
            ]);
        }

        Log::info('Gudang category deleted', [
            'category_id' => $category->id,
            'name' => $category->name,
            'user_id' => auth()->id(),
        ]);

        return $category->delete();
    }

    public function getActiveCategories()
    {
        return Category::active()->orderBy('name')->get(['id', 'name']);
    }
}
