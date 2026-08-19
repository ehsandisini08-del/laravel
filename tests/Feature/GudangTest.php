<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('gudang stok page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('gudang.stok'));

    $response->assertStatus(200)
        ->assertSee('Stok Barang');
});

test('gudang barang masuk page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('gudang.barang-masuk'));

    $response->assertStatus(200)
        ->assertSee('Barang Masuk');
});

test('gudang barang keluar page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('gudang.barang-keluar'));

    $response->assertStatus(200)
        ->assertSee('Barang Keluar');
});
