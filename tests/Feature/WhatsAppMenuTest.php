<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access the whatsapp menu page', function () {
    $this->get(route('whatsapp.menu'))->assertRedirect(route('login'));
});

test('admin can access the whatsapp menu page', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('whatsapp.menu'))
        ->assertOk()
        ->assertSee('WhatsApp Menu')
        ->assertSee(route('whatsapp.devices.index'))
        ->assertSee(route('whatsapp.broadcast.create'));
});

test('dashboard renders the mobile menu launcher', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Management', false)
        ->assertSee('PPP Secrets', false)
        ->assertSee(route('whatsapp.menu'))
        ->assertSee(route('customers.import.form'));
});
