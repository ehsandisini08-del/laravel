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

test('gudang riwayat page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('gudang.riwayat'));

    $response->assertStatus(200)
        ->assertSee('Riwayat / Jejak Stok');
});

test('gudang opname pages are accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('gudang.opname.index'))->assertStatus(200);
    $this->get(route('gudang.opname.create'))->assertStatus(200);
});

test('gudang pages are forbidden for admin area users', function () {
    $user = User::factory()->adminArea()->create();
    $this->actingAs($user);

    $this->get(route('gudang.stok'))->assertForbidden();
    $this->get(route('gudang.barang-masuk'))->assertForbidden();
    $this->get(route('gudang.barang-keluar'))->assertForbidden();
    $this->get(route('gudang.riwayat'))->assertForbidden();
    $this->get(route('gudang.opname.index'))->assertForbidden();
    $this->get(route('gudang.barang.index'))->assertForbidden();
    $this->get(route('gudang.kategori.index'))->assertForbidden();
});
