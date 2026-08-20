<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBarangKeluarRequest;
use App\Http\Requests\StoreBarangMasukRequest;
use App\Models\StockTransaction;
use App\Services\ActivityLoggerService;
use App\Services\GudangBarangService;
use App\Services\GudangService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class GudangController extends Controller
{
    public function __construct(
        protected GudangService $gudangService,
        protected GudangBarangService $gudangBarangService,
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function stok(Request $request)
    {
        $filters = $request->only(['search', 'category_id', 'stock_status', 'status']);

        return view('gudang.stok', [
            'summary' => $this->gudangService->getStockSummary(),
            'items' => $this->gudangService->getItems($filters),
            'categories' => $this->gudangBarangService->getActiveCategories(),
        ]);
    }

    public function barangMasuk(Request $request)
    {
        $filters = $request->only(['search', 'from', 'to']);

        return view('gudang.barang-masuk', [
            'transactions' => $this->gudangService->getTransactions(StockTransaction::TYPE_IN, $filters),
            'items' => $this->gudangService->getActiveItems(),
        ]);
    }

    public function storeBarangMasuk(StoreBarangMasukRequest $request)
    {
        try {
            $transaction = $this->gudangService->stockIn($request->validated());

            $this->activityLogger->created(
                'Gudang',
                "Barang masuk {$transaction->transaction_number} dicatat",
                $transaction,
                ['transaction_number' => $transaction->transaction_number]
            );

            return redirect()->route('gudang.barang-masuk')
                ->with('success', "Barang masuk {$transaction->transaction_number} berhasil dicatat.");
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to record stock in', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withInput()->with('error', 'Gagal mencatat barang masuk: '.$e->getMessage());
        }
    }

    public function barangKeluar(Request $request)
    {
        $filters = $request->only(['search', 'from', 'to']);

        return view('gudang.barang-keluar', [
            'transactions' => $this->gudangService->getTransactions(StockTransaction::TYPE_OUT, $filters),
            'items' => $this->gudangService->getActiveItems(),
        ]);
    }

    public function storeBarangKeluar(StoreBarangKeluarRequest $request)
    {
        try {
            $transaction = $this->gudangService->stockOut($request->validated());

            $this->activityLogger->created(
                'Gudang',
                "Barang keluar {$transaction->transaction_number} dicatat",
                $transaction,
                ['transaction_number' => $transaction->transaction_number]
            );

            return redirect()->route('gudang.barang-keluar')
                ->with('success', "Barang keluar {$transaction->transaction_number} berhasil dicatat.");
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to record stock out', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withInput()->with('error', 'Gagal mencatat barang keluar: '.$e->getMessage());
        }
    }
}
