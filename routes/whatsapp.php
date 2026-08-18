<?php

use App\Http\Controllers\WhatsApp\WaBroadcastController;
use App\Http\Controllers\WhatsApp\WaDashboardController;
use App\Http\Controllers\WhatsApp\WaDeviceController;
use App\Http\Controllers\WhatsApp\WaMessageController;
use App\Http\Controllers\WhatsApp\WaSettingsController;
use App\Http\Controllers\WhatsApp\WaTemplateController;
use App\Http\Controllers\WhatsApp\WaWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin', 'admin-area.restricted'])->prefix('whatsapp')->name('whatsapp.')->group(function () {
    Route::get('/', [WaDashboardController::class, 'index'])->name('dashboard');
    Route::get('menu', [WaDashboardController::class, 'menu'])->name('menu');

    Route::get('devices', [WaDeviceController::class, 'index'])->name('devices.index');
    Route::get('devices/create', [WaDeviceController::class, 'create'])->name('devices.create');
    Route::post('devices', [WaDeviceController::class, 'store'])->name('devices.store');
    Route::get('devices/{device}', [WaDeviceController::class, 'show'])->name('devices.show');
    Route::delete('devices/{device}', [WaDeviceController::class, 'destroy'])->name('devices.destroy');
    Route::post('devices/{device}/qr', [WaDeviceController::class, 'generateQr'])->name('devices.qr');
    Route::get('devices/{device}/status', [WaDeviceController::class, 'status'])->name('devices.status');
    Route::post('devices/{device}/disconnect', [WaDeviceController::class, 'disconnect'])->name('devices.disconnect');
    Route::post('devices/{device}/logout', [WaDeviceController::class, 'logout'])->name('devices.logout');
    Route::post('devices/sync', [WaDeviceController::class, 'sync'])->name('devices.sync');

    Route::get('templates', [WaTemplateController::class, 'index'])->name('templates.index');
    Route::get('templates/create', [WaTemplateController::class, 'create'])->name('templates.create');
    Route::post('templates', [WaTemplateController::class, 'store'])->name('templates.store');
    Route::get('templates/{template}/edit', [WaTemplateController::class, 'edit'])->name('templates.edit');
    Route::put('templates/{template}', [WaTemplateController::class, 'update'])->name('templates.update');
    Route::delete('templates/{template}', [WaTemplateController::class, 'destroy'])->name('templates.destroy');
    Route::post('templates/preview', [WaTemplateController::class, 'preview'])->name('templates.preview');

    Route::get('messages', [WaMessageController::class, 'index'])->name('messages.index');
    Route::get('messages/create', [WaMessageController::class, 'create'])->name('messages.create');
    Route::post('messages', [WaMessageController::class, 'store'])->name('messages.store');
    Route::get('messages/{message}', [WaMessageController::class, 'show'])->name('messages.show');

    Route::get('broadcast', [WaBroadcastController::class, 'create'])->name('broadcast.create');
    Route::post('broadcast', [WaBroadcastController::class, 'store'])->name('broadcast.store');

    Route::get('settings', [WaSettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [WaSettingsController::class, 'update'])->name('settings.update');
});

Route::post('/webhooks/whatsapp', WaWebhookController::class)->name('webhooks.whatsapp');
