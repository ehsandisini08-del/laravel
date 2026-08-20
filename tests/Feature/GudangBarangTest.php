<?php

use App\Models\Category;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('item can be created with factory', function () {
    $item = Item::factory()->create(['code' => 'BRG-0001', 'name' => 'Modem ONT', 'current_stock' => 0]);

    expect($item->code)->toBe('BRG-0001')
        ->and($item->name)->toBe('Modem ONT')
        ->and($item->is_active)->toBeTrue()
        ->and($item->current_stock)->toBe(0);
});

test('item code must be unique', function () {
    Item::factory()->create(['code' => 'BRG-0001']);

    expect(fn () => Item::factory()->create(['code' => 'BRG-0001']))
        ->toThrow(QueryException::class);
});

test('item index page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Item::factory()->count(3)->create();

    $this->get(route('gudang.barang.index'))->assertStatus(200);
});

test('item create page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('gudang.barang.create'))->assertStatus(200);
});

test('item can be stored via controller', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = Category::factory()->create();

    $response = $this->post(route('gudang.barang.store'), [
        'code' => 'brg-0001',
        'name' => 'Modem ONT',
        'category_id' => $category->id,
        'unit' => 'pcs',
        'min_stock' => 5,
    ]);

    $response->assertRedirect(route('gudang.barang.index'));
    $response->assertSessionHas('success');

    expect(Item::where('code', 'BRG-0001')->exists())->toBeTrue()
        ->and(Item::first()->current_stock)->toBe(0);
});

test('item store requires unique code', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Item::factory()->create(['code' => 'BRG-0001']);

    $this->post(route('gudang.barang.store'), [
        'code' => 'BRG-0001',
        'name' => 'Modem ONT',
        'unit' => 'pcs',
        'min_stock' => 0,
    ])->assertSessionHasErrors('code');
});

test('item show page shows movement history', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $item = Item::factory()->create(['current_stock' => 10]);
    StockMovement::factory()->create([
        'item_id' => $item->id,
        'quantity' => 10,
        'stock_before' => 0,
        'stock_after' => 10,
    ]);

    $response = $this->get(route('gudang.barang.show', $item));

    $response->assertStatus(200)
        ->assertSee($item->name)
        ->assertSee('Masuk');
});

test('item can be updated via controller', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $item = Item::factory()->create(['code' => 'BRG-0001']);

    $response = $this->put(route('gudang.barang.update', $item), [
        'code' => 'BRG-0002',
        'name' => 'Modem ONT Baru',
        'unit' => 'pcs',
        'min_stock' => 3,
    ]);

    $response->assertRedirect(route('gudang.barang.index'));
    $response->assertSessionHas('success');

    expect($item->fresh()->code)->toBe('BRG-0002')
        ->and($item->fresh()->name)->toBe('Modem ONT Baru');
});

test('item can be deleted via controller', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $item = Item::factory()->create();

    $response = $this->delete(route('gudang.barang.destroy', $item));

    $response->assertRedirect(route('gudang.barang.index'));
    $response->assertSessionHas('success');

    expect(Item::find($item->id))->toBeNull();
});

test('item with movement history cannot be deleted', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $item = Item::factory()->create();
    StockMovement::factory()->create(['item_id' => $item->id]);

    $response = $this->delete(route('gudang.barang.destroy', $item));

    $response->assertSessionHas('error');

    expect(Item::find($item->id))->not->toBeNull();
});

test('item low stock and out of stock scopes work', function () {
    Item::factory()->create(['current_stock' => 3, 'min_stock' => 5]);
    Item::factory()->create(['current_stock' => 0, 'min_stock' => 5]);
    Item::factory()->create(['current_stock' => 100, 'min_stock' => 5]);

    expect(Item::outOfStock()->count())->toBe(1)
        ->and(Item::lowStock()->where('current_stock', '>', 0)->count())->toBe(1);
});
