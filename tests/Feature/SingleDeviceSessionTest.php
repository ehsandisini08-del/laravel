<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function createFakeSession(string $id, int $userId): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $userId,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test-agent',
        'payload' => base64_encode(serialize([])),
        'last_activity' => now()->timestamp,
    ]);
}

test('admin login invalidates the previous device session', function () {
    $user = User::factory()->create();

    createFakeSession('old-device-session', $user->id);
    $user->forceFill(['active_session_id' => 'old-device-session', 'remember_token' => 'stale-remember-token'])->save();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    expect(DB::table('sessions')->where('id', 'old-device-session')->exists())->toBeFalse()
        ->and($user->fresh()->active_session_id)->toBe(session()->getId())
        ->and($user->fresh()->remember_token)->toBeNull()
        ->and(session()->getId())->not->toBe('old-device-session');
});

test('admin logout clears the active session marker', function () {
    $user = User::factory()->create([
        'active_session_id' => 'some-session',
    ]);

    $this->actingAs($user)->post('/logout');

    expect($user->fresh()->active_session_id)->toBeNull();
});

test('customer login invalidates the previous device session', function () {
    $customer = Customer::factory()->withPortal('123')->create();

    createFakeSession('old-customer-device-session', $customer->id);
    $customer->forceFill(['active_session_id' => 'old-customer-device-session', 'remember_token' => 'stale-remember-token'])->save();

    $this->post(route('portal.login'), [
        'customer_code' => $customer->customer_code,
        'password' => '123',
    ])->assertRedirect(route('portal.dashboard'));

    expect(DB::table('sessions')->where('id', 'old-customer-device-session')->exists())->toBeFalse()
        ->and($customer->fresh()->active_session_id)->toBe(session()->getId())
        ->and($customer->fresh()->remember_token)->toBeNull()
        ->and(session()->getId())->not->toBe('old-customer-device-session');
});

test('customer logout clears the active session marker', function () {
    $customer = Customer::factory()->withPortal('123')->create([
        'active_session_id' => 'some-customer-session',
    ]);

    $this->actingAs($customer, 'customer')->post(route('portal.logout'));

    expect($customer->fresh()->active_session_id)->toBeNull();
});

test('admin login page does not offer remember me', function () {
    $this->get('/login')
        ->assertStatus(200)
        ->assertDontSee('remember_me')
        ->assertDontSee('Remember me');
});

test('customer login page does not offer remember me', function () {
    $this->get(route('portal.login'))
        ->assertStatus(200)
        ->assertDontSee('remember_me')
        ->assertDontSee('Ingat saya');
});
