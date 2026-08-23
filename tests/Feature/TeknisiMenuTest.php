<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('developer and superadmin can access all teknisi menu routes', function () {
    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);

    $this->get(route('teknisi.buat-tugas'))
        ->assertOk()
        ->assertSee('Buat Tugas')
        ->assertSee('Form Pembuatan Tugas Teknisi');

    $this->get(route('teknisi.tugas-perbaikan'))
        ->assertOk()
        ->assertSee('Tugas Perbaikan');

    $this->get(route('teknisi.laporan-harian'))
        ->assertOk()
        ->assertSee('Laporan Harian');

    $this->get(route('teknisi.laporan-pemasangan'))
        ->assertOk()
        ->assertSee('Laporan Pemasangan');

    $this->get(route('teknisi.pekerjaan'))
        ->assertOk()
        ->assertSee('Pekerjaan');
});

test('teknisi can access task list, reports, and jobs but cannot access buat tugas', function () {
    $teknisi = teknisiUser();
    $this->actingAs($teknisi);

    // Forbidden from Buat Tugas
    $this->get(route('teknisi.buat-tugas'))
        ->assertForbidden()
        ->assertSee('Akses Dibatasi');

    // Allowed to access operational menus
    $this->get(route('teknisi.tugas-perbaikan'))
        ->assertOk()
        ->assertSee('Tugas Perbaikan')
        ->assertDontSee('+ Buat Tugas Baru');

    $this->get(route('teknisi.laporan-harian'))
        ->assertOk()
        ->assertSee('Laporan Harian');

    $this->get(route('teknisi.laporan-pemasangan'))
        ->assertOk()
        ->assertSee('Laporan Pemasangan');

    $this->get(route('teknisi.pekerjaan'))
        ->assertOk()
        ->assertSee('Pekerjaan')
        ->assertDontSee('+ Buat Tugas Baru');
});

test('admin area user cannot access any teknisi menu routes', function () {
    $adminArea = adminAreaUser();
    $this->actingAs($adminArea);

    $this->get(route('teknisi.buat-tugas'))->assertForbidden();
    $this->get(route('teknisi.tugas-perbaikan'))->assertForbidden();
    $this->get(route('teknisi.laporan-harian'))->assertForbidden();
    $this->get(route('teknisi.laporan-pemasangan'))->assertForbidden();
    $this->get(route('teknisi.pekerjaan'))->assertForbidden();
});

test('sidebar and menu grid render appropriate teknisi navigation links based on role', function () {
    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('teknisi.buat-tugas'))
        ->assertSee(route('teknisi.tugas-perbaikan'))
        ->assertSee(route('teknisi.laporan-harian'))
        ->assertSee(route('teknisi.laporan-pemasangan'))
        ->assertSee(route('teknisi.pekerjaan'));

    $teknisi = teknisiUser();
    $this->actingAs($teknisi);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(route('teknisi.buat-tugas'))
        ->assertSee(route('teknisi.tugas-perbaikan'))
        ->assertSee(route('teknisi.laporan-harian'))
        ->assertSee(route('teknisi.laporan-pemasangan'))
        ->assertSee(route('teknisi.pekerjaan'));
});
