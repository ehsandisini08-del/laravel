<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('user management is restricted to developer and superadmin', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);
    $this->get(route('users.index'))->assertForbidden();

    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);
    $this->get(route('users.index'))->assertStatus(200);

    $developer = User::factory()->developer()->create();
    $this->actingAs($developer);
    $this->get(route('users.index'))->assertStatus(200);
});

test('developer can create a user', function () {
    $developer = User::factory()->developer()->create();
    $this->actingAs($developer);

    $response = $this->post(route('users.store'), [
        'name' => 'Staff Baru',
        'email' => 'staff@example.com',
        'password' => 'secret123',
        'role' => 'admin',
    ]);

    $response->assertRedirect(route('users.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'email' => 'staff@example.com',
        'role' => 'admin',
    ]);
});

test('developer can update user role and reset password', function () {
    $developer = User::factory()->developer()->create();
    $this->actingAs($developer);

    $user = User::factory()->create();

    $response = $this->put(route('users.update', $user), [
        'name' => 'Updated Name',
        'email' => $user->email,
        'password' => 'newpassword123',
        'role' => 'superadmin',
    ]);

    $response->assertRedirect(route('users.index'));
    $response->assertSessionHas('success');

    $user->refresh();

    expect($user->name)->toBe('Updated Name')
        ->and($user->role)->toBe('superadmin')
        ->and(Hash::check('newpassword123', $user->password))->toBeTrue();
});

test('developer can delete a user', function () {
    $developer = User::factory()->developer()->create();
    $this->actingAs($developer);

    $user = User::factory()->create();

    $response = $this->delete(route('users.destroy', $user));

    $response->assertRedirect(route('users.index'));
    $response->assertSessionHas('success');

    expect(User::find($user->id))->toBeNull();
});

test('cannot delete own account', function () {
    $developer = User::factory()->developer()->create();
    $this->actingAs($developer);

    $response = $this->delete(route('users.destroy', $developer));

    $response->assertSessionHas('error');

    expect(User::find($developer->id))->not->toBeNull();
});

test('cannot delete the last developer', function () {
    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);

    $developer = User::factory()->developer()->create();

    $response = $this->delete(route('users.destroy', $developer));

    $response->assertSessionHas('error');

    expect(User::find($developer->id))->not->toBeNull();
});

test('maintenance mode lets developer access everything', function () {
    Setting::set('maintenance_mode', '1', 'system');

    $developer = User::factory()->developer()->create();
    $this->actingAs($developer);

    $this->get(route('dashboard'))->assertStatus(200);
    $this->get(route('users.index'))->assertStatus(200);
    $this->get(route('settings.index'))->assertStatus(200);
});
