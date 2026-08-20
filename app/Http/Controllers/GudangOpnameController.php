<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGudangOpnameRequest;
use App\Models\StockTransaction;
use App\Services\ActivityLoggerService;
use App\Services\GudangService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class GudangOpnameController extends Controller
{
    public function __construct(
        protected GudangService $gudangService,
        private readonly ActivityLoggerService $activityLogger,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'from', 'to']);

        return view('gudang.opname.index', [
            'transactions' => $this->gudangService->getTransactions(StockTransaction::TYPE_ADJUSTMENT, $filters),
        ]);
    }

    public function create()
    {
        return view('gudang.opname.create', [
            'items' => $this->gudangService->getActiveItems(),
        ]);
    }

    public function store(StoreGudangOpnameRequest $request)
    {
        try {
            $transaction = $this->gudangService->adjust($request->validated());

            $this->activityLogger->created(
                'Gudang',
                "Stok opname {$transaction->transaction_number} dicatat",
                $transaction,
                ['transaction_number' => $transaction->transaction_number]
            );

            return redirect()->route('gudang.opname.index')
                ->with('success', "Stok opname {$transaction->transaction_number} berhasil dicatat.");
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to record stock adjustment', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withInput()->with('error', 'Gagal mencatat stok opname: '.$e->getMessage());
        }
    }
}
