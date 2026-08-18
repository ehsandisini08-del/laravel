<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function createFakeSession(string $id, int $userId, ?int $lastActivity = null): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $userId,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test-agent',
        'payload' => base64_encode(serialize([])),
        'last_activity' => $lastActivity ?? now()->timestamp,
    ]);
}

test('admin login is blocked while another session is active', function () {
    $user = User::factory()->create();

    createFakeSession('old-device-session', $user->id);
    $user->forceFill(['active_session_id' => 'old-device-session'])->save();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    expect(DB::table('sessions')->where('id', 'old-device-session')->exists())->toBeTrue()
        ->and($user->fresh()->active_session_id)->toBe('old-device-session')
        ->and($user->fresh()->active_installation_id)->toBeNull();
});

test('admin login is allowed when the previous session is gone', function () {
    $user = User::factory()->create([
        'active_session_id' => 'gone-session',
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    expect($user->fresh()->active_session_id)->toBe(session()->getId())
        ->and($user->fresh()->remember_token)->toBeNull();
});

test('admin login is allowed when the previous session has expired', function () {
    $user = User::factory()->create();

    $expired = now()->subMinutes((int) config('session.lifetime') + 5)->timestamp;
    createFakeSession('expired-session', $user->id, $expired);
    $user->forceFill(['active_session_id' => 'expired-session'])->save();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    expect(DB::table('sessions')->where('id', 'expired-session')->exists())->toBeFalse()
        ->and($user->fresh()->active_session_id)->toBe(session()->getId());
});

test('admin logout clears the active session markers', function () {
    $user = User::factory()->create([
        'active_session_id' => 'some-session',
        'active_installation_id' => 'some-installation',
    ]);

    $this->actingAs($user)->post('/logout');

    expect($user->fresh()->active_session_id)->toBeNull()
        ->and($user->fresh()->active_installation_id)->toBeNull();
});

test('customer login is blocked while another session is active', function () {
    $customer = Customer::factory()->withPortal('123')->create();

    createFakeSession('old-customer-device-session', $customer->id);
    $customer->forceFill(['active_session_id' => 'old-customer-device-session'])->save();

    $this->post(route('portal.login'), [
        'customer_code' => $customer->customer_code,
        'password' => '123',
    ])->assertRedirect(route('portal.login'))
        ->assertSessionHasErrors('customer_code')
        ->assertSessionHas('errors');

    expect(DB::table('sessions')->where('id', 'old-customer-device-session')->exists())->toBeTrue()
        ->and($customer->fresh()->active_session_id)->toBe('old-customer-device-session');
});

test('customer logout clears the active session markers', function () {
    $customer = Customer::factory()->withPortal('123')->create([
        'active_session_id' => 'some-customer-session',
        'active_installation_id' => 'some-installation',
    ]);

    $this->actingAs($customer, 'customer')->post(route('portal.logout'));

    expect($customer->fresh()->active_session_id)->toBeNull()
        ->and($customer->fresh()->active_installation_id)->toBeNull();
});

test('login binds the installation id from the cookie', function () {
    $user = User::factory()->create();

    $this->withCookie('installation_id', 'ABC123')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    expect($user->fresh()->active_installation_id)->toBe('ABC123');
});

test('login with the same installation id replaces its own session', function () {
    $user = User::factory()->create();

    createFakeSession('old-app-session', $user->id);
    $user->forceFill([
        'active_session_id' => 'old-app-session',
        'active_installation_id' => 'ABC123',
    ])->save();

    $this->withCookie('installation_id', 'ABC123')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    expect(DB::table('sessions')->where('id', 'old-app-session')->exists())->toBeFalse()
        ->and($user->fresh()->active_session_id)->toBe(session()->getId())
        ->and($user->fresh()->active_installation_id)->toBe('ABC123');
});

test('login with a different installation id invalidates the previous session', function () {
    $user = User::factory()->create();

    createFakeSession('old-app-session', $user->id);
    $user->forceFill([
        'active_session_id' => 'old-app-session',
        'active_installation_id' => 'ABC123',
    ])->save();

    $this->withCookie('installation_id', 'XYZ789')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    expect(DB::table('sessions')->where('id', 'old-app-session')->exists())->toBeFalse()
        ->and($user->fresh()->active_session_id)->toBe(session()->getId())
        ->and($user->fresh()->active_installation_id)->toBe('XYZ789');
});

test('request with a mismatched installation cookie logs the user out', function () {
    $user = User::factory()->create();

    createFakeSession('old-app-session', $user->id);
    $user->forceFill([
        'active_session_id' => 'old-app-session',
        'active_installation_id' => 'ABC123',
    ])->save();

    $this->actingAs($user)
        ->withCookie('installation_id', 'XYZ789')
        ->get(route('dashboard', absolute: false))
        ->assertRedirect(route('login'));

    expect(DB::table('sessions')->where('id', 'old-app-session')->exists())->toBeFalse()
        ->and($user->fresh()->active_session_id)->toBeNull()
        ->and($user->fresh()->active_installation_id)->toBeNull();
});

test('request with a matching installation cookie keeps the session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard', absolute: false));

    $sessionId = session()->getId();

    $user->forceFill([
        'active_session_id' => $sessionId,
        'active_installation_id' => 'ABC123',
    ])->save();

    $this->withCookie(config('session.cookie'), $sessionId)
        ->withCookie('installation_id', 'ABC123')
        ->get(route('dashboard', absolute: false))
        ->assertOk();

    expect($user->fresh()->active_session_id)->toBe($sessionId);
});

test('a stale session is logged out while the active session is kept', function () {
    $user = User::factory()->create();

    createFakeSession('active-session', $user->id);
    $user->forceFill([
        'active_session_id' => 'active-session',
        'active_installation_id' => 'ABC123',
    ])->save();

    $this->actingAs($user)
        ->withCookie('installation_id', 'ABC123')
        ->get(route('dashboard', absolute: false))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');

    expect(DB::table('sessions')->where('id', 'active-session')->exists())->toBeTrue()
        ->and($user->fresh()->active_session_id)->toBe('active-session')
        ->and($user->fresh()->active_installation_id)->toBe('ABC123');
});

test('customer request with a mismatched installation cookie logs the user out', function () {
    $customer = Customer::factory()->withPortal('123')->create();

    createFakeSession('old-customer-app-session', $customer->id);
    $customer->forceFill([
        'active_session_id' => 'old-customer-app-session',
        'active_installation_id' => 'ABC123',
    ])->save();

    $this->actingAs($customer, 'customer')
        ->withCookie('installation_id', 'XYZ789')
        ->get(route('portal.dashboard'))
        ->assertRedirect(route('portal.login'));

    expect(DB::table('sessions')->where('id', 'old-customer-app-session')->exists())->toBeFalse()
        ->and($customer->fresh()->active_session_id)->toBeNull()
        ->and($customer->fresh()->active_installation_id)->toBeNull();
});

test('blocked customer login stays on the portal login page', function () {
    $customer = Customer::factory()->withPortal('123')->create();

    createFakeSession('old-customer-device-session', $customer->id);
    $customer->forceFill(['active_session_id' => 'old-customer-device-session'])->save();

    $this->followingRedirects()
        ->from(route('portal.login'))
        ->post(route('portal.login'), [
            'customer_code' => $customer->customer_code,
            'password' => '123',
        ])
        ->assertOk()
        ->assertSee('Akun sedang aktif di perangkat lain');
});

test('blocked admin login stays on the admin login page', function () {
    $user = User::factory()->create();

    createFakeSession('old-device-session', $user->id);
    $user->forceFill(['active_session_id' => 'old-device-session'])->save();

    $this->followingRedirects()
        ->from(route('login'))
        ->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertOk()
        ->assertSee('Akun sedang aktif di perangkat lain');
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
