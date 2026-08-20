<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGudangBarangRequest;
use App\Http\Requests\UpdateGudangBarangRequest;
use App\Models\Item;
use App\Services\ActivityLoggerService;
use App\Services\GudangBarangService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GudangBarangController extends Controller
{
    public function __construct(
        protected GudangBarangService $gudangBarangService,
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'category_id', 'status']);

        return view('gudang.barang.index', [
            'items' => $this->gudangBarangService->getItems($filters),
            'categories' => $this->gudangBarangService->getActiveCategories(),
        ]);
    }

    public function create()
    {
        return view('gudang.barang.create', [
            'categories' => $this->gudangBarangService->getActiveCategories(),
        ]);
    }

    public function store(StoreGudangBarangRequest $request)
    {
        try {
            $item = $this->gudangBarangService->createItem($request->validated());

            $this->activityLogger->created('Gudang', "Barang {$item->name} dibuat", $item);

            return redirect()->route('gudang.barang.index')
                ->with('success', 'Barang created successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create gudang item', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withInput()->with('error', 'Gagal membuat Barang: '.$e->getMessage());
        }
    }

    public function show(Item $item)
    {
        return view('gudang.barang.show', [
            'item' => $item->load('category'),
            'movements' => $item->movements()
                ->with(['user', 'transaction'])
                ->orderByDesc('moved_at')
                ->take(20)
                ->get(),
        ]);
    }

    public function edit(Item $item)
    {
        return view('gudang.barang.edit', [
            'item' => $item,
            'categories' => $this->gudangBarangService->getActiveCategories(),
        ]);
    }

    public function update(UpdateGudangBarangRequest $request, Item $item)
    {
        try {
            $this->gudangBarangService->updateItem($item, $request->validated());

            $this->activityLogger->updated('Gudang', "Barang #{$item->id} diubah", $item);

            return redirect()->route('gudang.barang.index')
                ->with('success', 'Barang updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update gudang item', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Gagal mengubah Barang: '.$e->getMessage());
        }
    }

    public function destroy(Item $item)
    {
        try {
            $this->gudangBarangService->deleteItem($item);

            $this->activityLogger->deleted('Gudang', "Barang #{$item->id} dihapus", $item);

            return redirect()->route('gudang.barang.index')
                ->with('success', 'Barang deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete gudang item', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menghapus Barang: '.$e->getMessage());
        }
    }
}
