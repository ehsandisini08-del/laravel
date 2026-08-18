<?php

use App\Models\Area;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('user management is restricted to developer and superadmin', function () {
    $admin = User::factory()->adminArea()->create();
    $this->actingAs($admin);
    $this->get(route('users.index'))->assertForbidden();

    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);
    $this->get(route('users.index'))->assertStatus(200);

    $developer = User::factory()->developer()->create();
    $this->actingAs($developer);
    $this->get(route('users.index'))->assertStatus(200);
});

test('developer can create an admin area user with areas', function () {
    $developer = User::factory()->developer()->create();
    $this->actingAs($developer);

    $area = Area::factory()->create();
    $secondArea = Area::factory()->create();

    $response = $this->post(route('users.store'), [
        'name' => 'Staff Baru',
        'email' => 'staff@example.com',
        'password' => 'secret123',
        'role' => 'admin_area',
        'areas' => [$area->id, $secondArea->id],
    ]);

    $response->assertRedirect(route('users.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'email' => 'staff@example.com',
        'role' => 'admin_area',
    ]);

    $user = User::where('email', 'staff@example.com')->first();

    expect($user->areas->pluck('id')->all())->toBe([$area->id, $secondArea->id]);
});

test('admin area user requires at least one area', function () {
    $developer = User::factory()->developer()->create();
    $this->actingAs($developer);

    $this->post(route('users.store'), [
        'name' => 'Staff Tanpa Area',
        'email' => 'staff2@example.com',
        'password' => 'secret123',
        'role' => 'admin_area',
    ])->assertSessionHasErrors('areas');

    $this->assertDatabaseMissing('users', ['email' => 'staff2@example.com']);
});

test('role admin is no longer valid', function () {
    $developer = User::factory()->developer()->create();
    $this->actingAs($developer);

    $this->post(route('users.store'), [
        'name' => 'Staff Lama',
        'email' => 'staff3@example.com',
        'password' => 'secret123',
        'role' => 'admin',
    ])->assertSessionHasErrors('role');

    $this->assertDatabaseMissing('users', ['email' => 'staff3@example.com']);
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
