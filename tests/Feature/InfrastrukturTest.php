<?php

use App\Models\Odc;
use App\Models\Odp;
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

test('infrastruktur map tab passes odc and odp data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $odc = Odc::factory()->create(['kode_odc' => 'ODC-MAP']);
    $odp = Odp::factory()->create([
        'odc_id' => $odc->id,
        'kode_odp' => 'ODP-MAP',
    ]);

    $response = $this->get(route('infrastruktur.index'));

    $response->assertStatus(200)
        ->assertSee('infrastruktur-map')
        ->assertSee('ODC-MAP')
        ->assertSee('ODP-MAP');
});
