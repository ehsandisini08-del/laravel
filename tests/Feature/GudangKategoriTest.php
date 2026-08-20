<?php

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('category can be created with factory', function () {
    $category = Category::factory()->create(['name' => 'Modem']);

    expect($category->name)->toBe('Modem')
        ->and($category->is_active)->toBeTrue();
});

test('category name must be unique', function () {
    Category::factory()->create(['name' => 'Modem']);

    expect(fn () => Category::factory()->create(['name' => 'Modem']))
        ->toThrow(QueryException::class);
});

test('category index page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Category::factory()->count(3)->create();

    $this->get(route('gudang.kategori.index'))->assertStatus(200);
});

test('category create page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('gudang.kategori.create'))->assertStatus(200);
});

test('category can be stored via controller', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('gudang.kategori.store'), [
        'name' => 'Modem / CPE',
    ]);

    $response->assertRedirect(route('gudang.kategori.index'));
    $response->assertSessionHas('success');

    expect(Category::where('name', 'Modem / CPE')->exists())->toBeTrue();
});

test('category can be updated via controller', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = Category::factory()->create();

    $response = $this->put(route('gudang.kategori.update', $category), [
        'name' => 'Kabel',
    ]);

    $response->assertRedirect(route('gudang.kategori.index'));
    $response->assertSessionHas('success');

    expect($category->fresh()->name)->toBe('Kabel');
});

test('category can be deleted via controller', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = Category::factory()->create();

    $response = $this->delete(route('gudang.kategori.destroy', $category));

    $response->assertRedirect(route('gudang.kategori.index'));
    $response->assertSessionHas('success');

    expect(Category::find($category->id))->toBeNull();
});

test('category with items cannot be deleted', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = Category::factory()->create();
    Item::factory()->create(['category_id' => $category->id]);

    $response = $this->delete(route('gudang.kategori.destroy', $category));

    $response->assertSessionHas('error');

    expect(Category::find($category->id))->not->toBeNull();
});
