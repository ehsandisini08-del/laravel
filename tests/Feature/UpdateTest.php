<?php

use App\Models\User;
use Illuminate\Support\Facades\Process;

afterEach(function () {
    @unlink(storage_path('app/update.lock'));
    @unlink(storage_path('app/update-status.json'));
});

test('developer can view the update page', function () {
    Process::fake();

    $developer = User::factory()->developer()->create();
    $this->actingAs($developer);

    $this->get(route('update.index'))
        ->assertStatus(200)
        ->assertSee('Update Aplikasi');
});

test('non developer admin cannot access the update page', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    $this->get(route('update.index'))->assertForbidden();
});

test('update run starts the background update process', function () {
    Process::fake();

    $developer = User::factory()->developer()->create();
    $this->actingAs($developer);

    $response = $this->post(route('update.run'));

    $response->assertRedirect(route('update.index'));
    $response->assertSessionHas('success');

    Process::assertRan(fn ($process) => str_contains($process->command, 'artisan app:update'));
});

test('update run is rejected while another update is running', function () {
    Process::fake();

    $developer = User::factory()->developer()->create();
    $this->actingAs($developer);

    file_put_contents(storage_path('app/update.lock'), (string) now()->timestamp);

    $response = $this->post(route('update.run'));

    $response->assertRedirect(route('update.index'));
    $response->assertSessionHas('error');

    Process::assertNothingRan();
});

test('app:update command runs deployment steps and reports success', function () {
    Process::fake();

    $exitCode = $this->artisan('app:update')->run();

    expect($exitCode)->toBe(0)
        ->and(file_exists(storage_path('app/update.lock')))->toBeFalse();

    Process::assertRan(fn ($process) => str_contains($process->command, 'git fetch origin main'));
    Process::assertRan(fn ($process) => str_contains($process->command, 'composer install --no-dev'));
    Process::assertRan(fn ($process) => str_contains($process->command, 'artisan migrate --force'));
    Process::assertRan(fn ($process) => str_contains($process->command, 'npm run build'));
    Process::assertRan(fn ($process) => str_contains($process->command, 'artisan optimize'));

    $status = json_decode((string) file_get_contents(storage_path('app/update-status.json')), true);

    expect($status['success'])->toBeTrue()
        ->and($status['finished_at'])->not->toBeNull();
});
