<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGudangKategoriRequest;
use App\Http\Requests\UpdateGudangKategoriRequest;
use App\Models\Category;
use App\Services\ActivityLoggerService;
use App\Services\GudangBarangService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GudangKategoriController extends Controller
{
    public function __construct(
        protected GudangBarangService $gudangBarangService,
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status']);

        return view('gudang.kategori.index', [
            'categories' => $this->gudangBarangService->getCategories($filters),
        ]);
    }

    public function create()
    {
        return view('gudang.kategori.create');
    }

    public function store(StoreGudangKategoriRequest $request)
    {
        try {
            $category = $this->gudangBarangService->createCategory($request->validated());

            $this->activityLogger->created('Gudang', "Kategori {$category->name} dibuat", $category);

            return redirect()->route('gudang.kategori.index')
                ->with('success', 'Kategori created successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create gudang category', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withInput()->with('error', 'Gagal membuat Kategori: '.$e->getMessage());
        }
    }

    public function show(Category $category)
    {
        return view('gudang.kategori.show', [
            'category' => $category->loadCount('items'),
        ]);
    }

    public function edit(Category $category)
    {
        return view('gudang.kategori.edit', compact('category'));
    }

    public function update(UpdateGudangKategoriRequest $request, Category $category)
    {
        try {
            $this->gudangBarangService->updateCategory($category, $request->validated());

            $this->activityLogger->updated('Gudang', "Kategori #{$category->id} diubah", $category);

            return redirect()->route('gudang.kategori.index')
                ->with('success', 'Kategori updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update gudang category', [
                'category_id' => $category->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Gagal mengubah Kategori: '.$e->getMessage());
        }
    }

    public function destroy(Category $category)
    {
        try {
            $this->gudangBarangService->deleteCategory($category);

            $this->activityLogger->deleted('Gudang', "Kategori #{$category->id} dihapus", $category);

            return redirect()->route('gudang.kategori.index')
                ->with('success', 'Kategori deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete gudang category', [
                'category_id' => $category->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menghapus Kategori: '.$e->getMessage());
        }
    }
}
