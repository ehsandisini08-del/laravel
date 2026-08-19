<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('infrastruktur index page is accessible', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('infrastruktur.index'));

    $response->assertStatus(200)
        ->assertSee('Infrastruktur')
        ->assertSee('ODC')
        ->assertSee('ODP')
        ->assertSee('MAP');
});
