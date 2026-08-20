<?php

namespace App\Services;

use App\Models\Item;
use App\Models\StockMovement;
use App\Models\StockTransaction;
use App\Models\StockTransactionItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class GudangService
{
    public function stockIn(array $data): StockTransaction
    {
        return $this->createTransaction(StockTransaction::TYPE_IN, $data);
    }

    public function stockOut(array $data): StockTransaction
    {
        return $this->createTransaction(StockTransaction::TYPE_OUT, $data);
    }

    public function adjust(array $data): StockTransaction
    {
        return $this->createTransaction(StockTransaction::TYPE_ADJUSTMENT, $data);
    }

    protected function createTransaction(string $type, array $data): StockTransaction
    {
        return DB::transaction(function () use ($type, $data) {
            $transaction = StockTransaction::create([
                'transaction_number' => $this->nextTransactionNumber($type),
                'type' => $type,
                'reference' => $data['reference'] ?? null,
                'supplier' => $data['supplier'] ?? null,
                'recipient' => $data['recipient'] ?? null,
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'user_id' => auth()->id(),
                'transaction_date' => $data['transaction_date'] ?? now(),
            ]);

            if ($type === StockTransaction::TYPE_ADJUSTMENT) {
                $this->applyAdjustment($transaction, $data);
            } else {
                $this->applyLines($transaction, $type, $data['items']);
            }

            Log::info('Stock transaction created', [
                'transaction_id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'type' => $type,
                'user_id' => auth()->id(),
            ]);

            return $transaction;
        });
    }

    protected function applyLines(StockTransaction $transaction, string $type, array $lines): void
    {
        foreach ($lines as $line) {
            /** @var Item $item */
            $item = Item::findOrFail($line['item_id']);
            $quantity = (int) $line['quantity'];
            $sign = $type === StockTransaction::TYPE_IN ? 1 : -1;

            if ($type === StockTransaction::TYPE_OUT && $item->current_stock < $quantity) {
                throw ValidationException::withMessages([
                    'items' => "Stok {$item->name} tidak mencukupi (tersedia {$item->current_stock} {$item->unit}).",
                ]);
            }

            $before = $item->current_stock;

            $item->update([
                'current_stock' => $before + ($sign * $quantity),
            ]);

            StockTransactionItem::create([
                'stock_transaction_id' => $transaction->id,
                'item_id' => $item->id,
                'quantity' => $quantity,
            ]);

            StockMovement::create([
                'item_id' => $item->id,
                'stock_transaction_id' => $transaction->id,
                'type' => $type,
                'quantity' => $sign * $quantity,
                'stock_before' => $before,
                'stock_after' => $before + ($sign * $quantity),
                'user_id' => $transaction->user_id,
                'moved_at' => $transaction->transaction_date,
            ]);
        }
    }

    protected function applyAdjustment(StockTransaction $transaction, array $data): void
    {
        /** @var Item $item */
        $item = Item::findOrFail($data['item_id']);
        $physicalStock = (int) $data['quantity'];
        $difference = $physicalStock - $item->current_stock;

        if ($difference === 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Stok fisik sama dengan stok saat ini, tidak ada perubahan.',
            ]);
        }

        $before = $item->current_stock;

        $item->update([
            'current_stock' => $physicalStock,
        ]);

        StockTransactionItem::create([
            'stock_transaction_id' => $transaction->id,
            'item_id' => $item->id,
            'quantity' => $difference,
        ]);

        StockMovement::create([
            'item_id' => $item->id,
            'stock_transaction_id' => $transaction->id,
            'type' => StockTransaction::TYPE_ADJUSTMENT,
            'quantity' => $difference,
            'stock_before' => $before,
            'stock_after' => $physicalStock,
            'user_id' => $transaction->user_id,
            'moved_at' => $transaction->transaction_date,
        ]);
    }

    public function nextTransactionNumber(string $type): string
    {
        $prefix = match ($type) {
            StockTransaction::TYPE_IN => StockTransaction::PREFIX_IN,
            StockTransaction::TYPE_OUT => StockTransaction::PREFIX_OUT,
            StockTransaction::TYPE_ADJUSTMENT => StockTransaction::PREFIX_ADJUSTMENT,
            default => 'TRX',
        };

        $month = now()->format('Ym');

        $latest = StockTransaction::where('transaction_number', 'like', "{$prefix}-{$month}-%")
            ->orderByDesc('transaction_number')
            ->value('transaction_number');

        $sequence = $latest ? ((int) substr($latest, -4)) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $month, $sequence);
    }

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

        if (! empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'low') {
                $query->lowStock()->where('current_stock', '>', 0);
            } elseif ($filters['stock_status'] === 'out') {
                $query->outOfStock();
            }
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return $query->orderBy('name')->paginate(15)->withQueryString();
    }

    public function getTransactions(string $type, array $filters = [])
    {
        $query = StockTransaction::with(['user', 'items.item'])
            ->ofType($type);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('supplier', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['from'])) {
            $query->whereDate('transaction_date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('transaction_date', '<=', $filters['to']);
        }

        return $query->orderByDesc('transaction_date')->paginate(15)->withQueryString();
    }

    public function getMovements(array $filters = [])
    {
        $query = StockMovement::with(['item.category', 'user', 'transaction'])
            ->orderByDesc('moved_at');

        if (! empty($filters['item_id'])) {
            $query->where('item_id', $filters['item_id']);
        }

        if (! empty($filters['type']) && in_array($filters['type'], [StockTransaction::TYPE_IN, StockTransaction::TYPE_OUT, StockTransaction::TYPE_ADJUSTMENT], true)) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('moved_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('moved_at', '<=', $filters['to']);
        }

        return $query->paginate(20)->withQueryString();
    }

    public function getStockSummary(): array
    {
        return [
            'total_items' => Item::count(),
            'total_stock' => (int) Item::sum('current_stock'),
            'low_stock' => Item::lowStock()->where('current_stock', '>', 0)->count(),
            'out_of_stock' => Item::outOfStock()->count(),
        ];
    }

    public function getActiveItems()
    {
        return Item::active()->orderBy('name')->get(['id', 'code', 'name', 'unit', 'current_stock']);
    }
}
