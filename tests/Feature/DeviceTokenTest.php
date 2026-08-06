<?php

use App\Models\Customer;
use App\Models\DeviceToken;
use App\Models\User;

test('customer can register a device token', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($customer, 'customer');

    $response = $this->post(route('mobile.customer.device-token.store'), [
        'token' => 'fcm-customer-token-1',
        'platform' => 'android',
        'device_name' => 'Xiaomi Redmi',
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('device_tokens', [
        'user_type' => DeviceToken::TYPE_CUSTOMER,
        'user_id' => $customer->id,
        'token' => 'fcm-customer-token-1',
        'platform' => 'android',
        'device_name' => 'Xiaomi Redmi',
    ]);
});

test('customer device token registration requires a token', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($customer, 'customer');

    $this->post(route('mobile.customer.device-token.store'), [])
        ->assertSessionHasErrors('token');
});

test('customer can remove a device token', function () {
    $customer = Customer::factory()->create();

    DeviceToken::create([
        'user_type' => DeviceToken::TYPE_CUSTOMER,
        'user_id' => $customer->id,
        'token' => 'fcm-remove-me',
    ]);

    $this->actingAs($customer, 'customer');

    $this->delete(route('mobile.customer.device-token.destroy', ['token' => 'fcm-remove-me']))
        ->assertOk();

    expect(DeviceToken::where('token', 'fcm-remove-me')->exists())->toBeFalse();
});

test('admin can register a device token', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->post(route('mobile.admin.device-token.store'), [
        'token' => 'fcm-admin-token-1',
        'platform' => 'android',
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('device_tokens', [
        'user_type' => DeviceToken::TYPE_ADMIN,
        'user_id' => $user->id,
        'token' => 'fcm-admin-token-1',
    ]);
});

test('device token routes require authentication', function () {
    $this->post(route('mobile.customer.device-token.store'), ['token' => 'x'])
        ->assertRedirect();

    $this->post(route('mobile.admin.device-token.store'), ['token' => 'x'])
        ->assertRedirect();
});
