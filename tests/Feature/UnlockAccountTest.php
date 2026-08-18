<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function createFakeUnlockSession(string $id, int $userId, ?int $lastActivity = null): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $userId,
        'ip_address' => '192.168.1.10',
        'user_agent' => 'test-agent',
        'payload' => base64_encode(serialize([])),
        'last_activity' => $lastActivity ?? now()->timestamp,
    ]);
}

test('unlock accounts page requires superadmin or developer role', function () {
    $admin = User::factory()->adminArea()->create();

    $this->actingAs($admin)->get(route('unlock-accounts.index'))->assertForbidden();

    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin)->get(route('unlock-accounts.index'))->assertOk();

    $developer = User::factory()->developer()->create();
    $this->actingAs($developer)->get(route('unlock-accounts.index'))->assertOk();
});

test('unlock accounts page lists only accounts with active sessions', function () {
    $this->actingAs(User::factory()->superadmin()->create());

    $lockedUser = User::factory()->create();
    $freeUser = User::factory()->create();

    createFakeUnlockSession('unlock-user-session', $lockedUser->id);
    $lockedUser->forceFill(['active_session_id' => 'unlock-user-session', 'active_installation_id' => 'INST-1'])->save();

    $lockedCustomer = Customer::factory()->create();
    $freeCustomer = Customer::factory()->create();

    createFakeUnlockSession('unlock-customer-session', $lockedCustomer->id);
    $lockedCustomer->forceFill(['active_session_id' => 'unlock-customer-session', 'active_installation_id' => 'INST-2'])->save();

    $response = $this->get(route('unlock-accounts.index'));

    $response->assertOk()
        ->assertSee($lockedUser->name)
        ->assertSee($lockedUser->email)
        ->assertSee('192.168.1.10')
        ->assertSee($lockedCustomer->name)
        ->assertSee($lockedCustomer->customer_code)
        ->assertDontSee($freeUser->name)
        ->assertDontSee($freeCustomer->name);
});

test('unlocking a user clears markers and deletes the session row', function () {
    $this->actingAs(User::factory()->superadmin()->create());

    $user = User::factory()->create();
    createFakeUnlockSession('unlock-user-session', $user->id);
    $user->forceFill(['active_session_id' => 'unlock-user-session', 'active_installation_id' => 'INST-1'])->save();

    $this->post(route('unlock-accounts.unlock-user', $user))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(DB::table('sessions')->where('id', 'unlock-user-session')->exists())->toBeFalse()
        ->and($user->fresh()->active_session_id)->toBeNull()
        ->and($user->fresh()->active_installation_id)->toBeNull();
});

test('unlocking a customer clears markers and deletes the session row', function () {
    $this->actingAs(User::factory()->superadmin()->create());

    $customer = Customer::factory()->withPortal('123')->create();
    createFakeUnlockSession('unlock-customer-session', $customer->id);
    $customer->forceFill(['active_session_id' => 'unlock-customer-session', 'active_installation_id' => 'INST-2'])->save();

    $this->post(route('unlock-accounts.unlock-customer', $customer))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(DB::table('sessions')->where('id', 'unlock-customer-session')->exists())->toBeFalse()
        ->and($customer->fresh()->active_session_id)->toBeNull()
        ->and($customer->fresh()->active_installation_id)->toBeNull();
});

test('unlock routes require superadmin or developer role', function () {
    $admin = User::factory()->adminArea()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('unlock-accounts.unlock-user', $user))
        ->assertForbidden();
});
