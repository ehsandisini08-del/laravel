<?php

use App\Models\WaDevice;
use App\Services\WhatsApp\BaileysGatewayService;
use App\Services\WhatsApp\WhatsAppGatewayService;

use function Pest\Laravel\mock;

beforeEach(function () {
    mock(BaileysGatewayService::class);
});

it('creates a device locally when gateway is unreachable', function () {
    app(BaileysGatewayService::class)->shouldReceive('createDevice')
        ->with('test-session')
        ->once()
        ->andReturn(['success' => false, 'error' => 'Connection refused']);

    $device = app(WhatsAppGatewayService::class)->createDevice('Test Device', 'test-session');

    expect($device->device_name)->toBe('Test Device');
    expect($device->session_name)->toBe('test-session');
    expect($device->status)->toBe('disconnected');
});

it('creates a device with qr_waiting status when gateway returns qr', function () {
    app(BaileysGatewayService::class)->shouldReceive('createDevice')
        ->with('test-session')
        ->once()
        ->andReturn([
            'success' => true,
            'data' => ['qr_code' => 'base64data', 'status' => 'qr_waiting'],
        ]);

    $device = app(WhatsAppGatewayService::class)->createDevice('Test Device', 'test-session');

    expect($device->status)->toBe('qr_waiting');
});

it('refreshes status from gateway', function () {
    $device = WaDevice::factory()->create(['status' => 'qr_waiting']);

    app(BaileysGatewayService::class)->shouldReceive('getStatus')
        ->with($device->session_name)
        ->once()
        ->andReturn([
            'success' => true,
            'data' => [
                'status' => 'connected',
                'phone_number' => '628123456789',
                'profile_name' => 'Test User',
            ],
        ]);

    $status = app(WhatsAppGatewayService::class)->refreshStatus($device);

    expect($status)->toBe('connected');
    expect($device->fresh()->status)->toBe('connected');
    expect($device->fresh()->phone_number)->toBe('628123456789');
});

it('returns current status when API call fails', function () {
    $device = WaDevice::factory()->create(['status' => 'qr_waiting']);

    app(BaileysGatewayService::class)->shouldReceive('getStatus')
        ->with($device->session_name)
        ->once()
        ->andReturn(['success' => false, 'error' => 'Connection refused']);

    $status = app(WhatsAppGatewayService::class)->refreshStatus($device);

    expect($status)->toBe('qr_waiting');
});

it('sends message and records it', function () {
    $device = WaDevice::factory()->connected()->create();

    app(BaileysGatewayService::class)->shouldReceive('sendText')
        ->with($device->session_name, '628123456789', 'Hello')
        ->once()
        ->andReturn([
            'success' => true,
            'data' => ['message_id' => 'msg-123'],
        ]);

    $message = app(WhatsAppGatewayService::class)->sendMessage($device, '628123456789', 'Hello');

    expect($message->status)->toBe('sent');
    expect($message->baileys_message_id)->toBe('msg-123');
    expect($message->direction)->toBe('outgoing');
});

it('checks gateway health', function () {
    app(BaileysGatewayService::class)->shouldReceive('health')
        ->once()
        ->andReturn(['success' => true, 'data' => ['status' => 'ok']]);

    $healthy = app(WhatsAppGatewayService::class)->checkGatewayHealth();

    expect($healthy)->toBeTrue();
});
