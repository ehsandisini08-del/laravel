<?php

use App\Http\Controllers\Mobile\DeviceTokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->middleware(['customer', 'installation:customer'])->group(function () {
    Route::post('customer/device-token', [DeviceTokenController::class, 'storeCustomer'])->name('mobile.customer.device-token.store');
    Route::delete('customer/device-token/{token}', [DeviceTokenController::class, 'destroyCustomer'])->name('mobile.customer.device-token.destroy');
});

Route::prefix('mobile')->middleware(['auth', 'installation'])->group(function () {
    Route::post('admin/device-token', [DeviceTokenController::class, 'storeAdmin'])->name('mobile.admin.device-token.store');
    Route::delete('admin/device-token/{token}', [DeviceTokenController::class, 'destroyAdmin'])->name('mobile.admin.device-token.destroy');
});
