<?php

use App\Http\Controllers\Billing\BillingDashboardController;
use App\Http\Controllers\Billing\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('billing')->name('billing.')->group(function () {
    Route::get('/', [BillingDashboardController::class, 'index'])->name('dashboard');
    Route::post('/generate', [BillingDashboardController::class, 'generate'])->name('generate');

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
});
