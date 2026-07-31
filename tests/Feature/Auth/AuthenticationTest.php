<?php

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('login failure does not cause internal server error', function () {
    $response = $this->post('/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('login failure creates activity log', function () {
    $this->post('/login', [
        'email' => 'failed@example.com',
        'password' => 'wrong-password',
    ]);

    $log = Activity::query()
        ->where('event', 'Login Failed')
        ->where('description', 'Authentication failed for failed@example.com')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->subject_type)->toBeNull();
    expect($log->subject_id)->toBeNull();
    expect($log->causer_type)->toBeNull();
    expect($log->causer_id)->toBeNull();
});

test('login failure with valid email but wrong password creates activity log', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $log = Activity::query()
        ->where('event', 'Login Failed')
        ->where('description', 'Authentication failed for '.$user->email)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->subject_type)->toBeNull();
    expect($log->subject_id)->toBeNull();
});

test('login failure preserves email input', function () {
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasInput('email', 'test@example.com');
});

test('successful login creates activity log', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $log = Activity::query()
        ->where('event', 'Login Success')
        ->where('causer_id', $user->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->subject_type)->toBeNull();
    expect($log->subject_id)->toBeNull();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
