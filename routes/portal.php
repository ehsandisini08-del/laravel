<?php

use App\Http\Controllers\Portal\AuthenticatedSessionController;
use App\Http\Controllers\Portal\PaymentController;
use App\Http\Controllers\Portal\PortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->name('portal.')->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('login');
    });

    Route::middleware('customer')->group(function () {
        Route::get('/', [PortalController::class, 'dashboard'])->name('dashboard');
        Route::get('invoices', [PortalController::class, 'invoices'])->name('invoices.index');
        Route::get('invoices/{invoice}', [PortalController::class, 'showInvoice'])->name('invoices.show');
        Route::post('invoices/{invoice}/pay', [PaymentController::class, 'pay'])->name('invoices.pay');
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    });
});
