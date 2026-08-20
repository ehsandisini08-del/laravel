<?php

use App\Models\Item;
use App\Models\StockMovement;
use App\Models\StockTransaction;
use App\Models\User;
use App\Services\GudangService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('barang masuk increments stock and writes ledger', function () {
    $this->actingAs(User::factory()->create());

    $item = Item::factory()->create(['current_stock' => 5]);

    $response = $this->post(route('gudang.barang-masuk.store'), [
        'supplier' => 'PT Supplier',
        'reference' => 'PO-001',
        'transaction_date' => now()->format('Y-m-d'),
        'items' => [
            ['item_id' => $item->id, 'quantity' => 10],
        ],
    ]);

    $response->assertRedirect(route('gudang.barang-masuk'));
    $response->assertSessionHas('success');

    expect($item->fresh()->current_stock)->toBe(15);

    $transaction = StockTransaction::first();
    expect($transaction->type)->toBe(StockTransaction::TYPE_IN)
        ->and($transaction->transaction_number)->toMatch('/^BM-\d{6}-\d{4}$/')
        ->and($transaction->supplier)->toBe('PT Supplier');

    $movement = StockMovement::where('item_id', $item->id)->first();
    expect($movement)->not->toBeNull()
        ->and($movement->quantity)->toBe(10)
        ->and($movement->stock_before)->toBe(5)
        ->and($movement->stock_after)->toBe(15)
        ->and($movement->user_id)->toBe(auth()->id());
});

test('barang masuk requires at least one item', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->post(route('gudang.barang-masuk.store'), [
        'transaction_date' => now()->format('Y-m-d'),
        'items' => [],
    ]);

    $response->assertSessionHasErrors('items');
    expect(StockTransaction::count())->toBe(0);
});

test('barang masuk with multiple items updates all stocks', function () {
    $this->actingAs(User::factory()->create());

    $first = Item::factory()->create(['current_stock' => 0]);
    $second = Item::factory()->create(['current_stock' => 2]);

    $this->post(route('gudang.barang-masuk.store'), [
        'transaction_date' => now()->format('Y-m-d'),
        'items' => [
            ['item_id' => $first->id, 'quantity' => 5],
            ['item_id' => $second->id, 'quantity' => 3],
        ],
    ])->assertSessionHas('success');

    expect($first->fresh()->current_stock)->toBe(5)
        ->and($second->fresh()->current_stock)->toBe(5)
        ->and(StockMovement::count())->toBe(2);
});

test('barang keluar decrements stock and writes negative ledger', function () {
    $this->actingAs(User::factory()->create());

    $item = Item::factory()->create(['current_stock' => 20]);

    $response = $this->post(route('gudang.barang-keluar.store'), [
        'recipient' => 'Tim Instalasi',
        'reason' => 'Instalasi baru',
        'transaction_date' => now()->format('Y-m-d'),
        'items' => [
            ['item_id' => $item->id, 'quantity' => 7],
        ],
    ]);

    $response->assertRedirect(route('gudang.barang-keluar'));
    $response->assertSessionHas('success');

    expect($item->fresh()->current_stock)->toBe(13);

    $transaction = StockTransaction::first();
    expect($transaction->type)->toBe(StockTransaction::TYPE_OUT)
        ->and($transaction->transaction_number)->toMatch('/^BK-\d{6}-\d{4}$/');

    $movement = StockMovement::where('item_id', $item->id)->first();
    expect($movement->quantity)->toBe(-7)
        ->and($movement->stock_before)->toBe(20)
        ->and($movement->stock_after)->toBe(13);
});

test('barang keluar with insufficient stock is rejected', function () {
    $this->actingAs(User::factory()->create());

    $item = Item::factory()->create(['current_stock' => 3]);

    $response = $this->post(route('gudang.barang-keluar.store'), [
        'transaction_date' => now()->format('Y-m-d'),
        'items' => [
            ['item_id' => $item->id, 'quantity' => 10],
        ],
    ]);

    $response->assertSessionHasErrors('items');

    expect($item->fresh()->current_stock)->toBe(3)
        ->and(StockTransaction::count())->toBe(0)
        ->and(StockMovement::count())->toBe(0);
});

test('stok opname adjusts stock with reason', function () {
    $this->actingAs(User::factory()->create());

    $item = Item::factory()->create(['current_stock' => 10]);

    $response = $this->post(route('gudang.opname.store'), [
        'item_id' => $item->id,
        'quantity' => 14,
        'reason' => 'Hasil stock opname bulanan',
        'transaction_date' => now()->format('Y-m-d'),
    ]);

    $response->assertRedirect(route('gudang.opname.index'));
    $response->assertSessionHas('success');

    expect($item->fresh()->current_stock)->toBe(14);

    $transaction = StockTransaction::first();
    expect($transaction->type)->toBe(StockTransaction::TYPE_ADJUSTMENT)
        ->and($transaction->transaction_number)->toMatch('/^SO-\d{6}-\d{4}$/')
        ->and($transaction->reason)->toBe('Hasil stock opname bulanan');

    $movement = StockMovement::where('item_id', $item->id)->first();
    expect($movement->quantity)->toBe(4)
        ->and($movement->stock_before)->toBe(10)
        ->and($movement->stock_after)->toBe(14);
});

test('stok opname can decrease stock', function () {
    $this->actingAs(User::factory()->create());

    $item = Item::factory()->create(['current_stock' => 10]);

    $this->post(route('gudang.opname.store'), [
        'item_id' => $item->id,
        'quantity' => 6,
        'reason' => 'Barang rusak ditemukan',
        'transaction_date' => now()->format('Y-m-d'),
    ])->assertSessionHas('success');

    expect($item->fresh()->current_stock)->toBe(6);

    $movement = StockMovement::where('item_id', $item->id)->first();
    expect($movement->quantity)->toBe(-4)
        ->and($movement->stock_after)->toBe(6);
});

test('stok opname requires reason', function () {
    $this->actingAs(User::factory()->create());

    $item = Item::factory()->create(['current_stock' => 10]);

    $response = $this->post(route('gudang.opname.store'), [
        'item_id' => $item->id,
        'quantity' => 5,
        'transaction_date' => now()->format('Y-m-d'),
    ]);

    $response->assertSessionHasErrors('reason');
    expect(StockTransaction::count())->toBe(0);
});

test('stok opname with unchanged stock is rejected', function () {
    $this->actingAs(User::factory()->create());

    $item = Item::factory()->create(['current_stock' => 10]);

    $response = $this->post(route('gudang.opname.store'), [
        'item_id' => $item->id,
        'quantity' => 10,
        'reason' => 'Tidak ada perubahan',
        'transaction_date' => now()->format('Y-m-d'),
    ]);

    $response->assertSessionHasErrors('quantity');
    expect(StockTransaction::count())->toBe(0);
});

test('transaction numbers are sequential per month', function () {
    $this->actingAs(User::factory()->create());

    $item = Item::factory()->create(['current_stock' => 0]);

    $this->post(route('gudang.barang-masuk.store'), [
        'transaction_date' => now()->format('Y-m-d'),
        'items' => [['item_id' => $item->id, 'quantity' => 1]],
    ])->assertSessionHas('success');

    $this->post(route('gudang.barang-masuk.store'), [
        'transaction_date' => now()->format('Y-m-d'),
        'items' => [['item_id' => $item->id, 'quantity' => 1]],
    ])->assertSessionHas('success');

    $numbers = StockTransaction::orderBy('id')->pluck('transaction_number')->all();

    expect($numbers[0])->toMatch('/-0001$/')
        ->and($numbers[1])->toMatch('/-0002$/')
        ->and($numbers[0])->not->toBe($numbers[1]);
});

test('riwayat page shows stock movements', function () {
    $this->actingAs(User::factory()->create());

    $item = Item::factory()->create(['current_stock' => 10]);

    $this->post(route('gudang.barang-masuk.store'), [
        'transaction_date' => now()->format('Y-m-d'),
        'items' => [['item_id' => $item->id, 'quantity' => 5]],
    ])->assertSessionHas('success');

    $response = $this->get(route('gudang.riwayat'));

    $response->assertStatus(200)
        ->assertSee($item->name)
        ->assertSee('+5');
});

test('gudang service stock in and out maintains ledger integrity', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $item = Item::factory()->create(['current_stock' => 0]);

    $service = app(GudangService::class);

    $service->stockIn([
        'transaction_date' => now()->format('Y-m-d'),
        'items' => [['item_id' => $item->id, 'quantity' => 8]],
    ]);

    $service->stockOut([
        'transaction_date' => now()->format('Y-m-d'),
        'items' => [['item_id' => $item->id, 'quantity' => 3]],
    ]);

    expect($item->fresh()->current_stock)->toBe(5);

    $movements = StockMovement::orderBy('id')->get();
    expect($movements)->toHaveCount(2)
        ->and($movements[0]->stock_before)->toBe(0)
        ->and($movements[0]->stock_after)->toBe(8)
        ->and($movements[1]->stock_before)->toBe(8)
        ->and($movements[1]->stock_after)->toBe(5);
});

test('gudang service rejects stock out below zero', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $item = Item::factory()->create(['current_stock' => 0]);

    $service = app(GudangService::class);

    expect(fn () => $service->stockOut([
        'transaction_date' => now()->format('Y-m-d'),
        'items' => [['item_id' => $item->id, 'quantity' => 1]],
    ]))->toThrow(ValidationException::class);
});
