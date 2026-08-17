<?php

use App\Http\Controllers\Billing\BillingDashboardController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('billing')->name('billing.')->group(function () {
    Route::get('/', [BillingDashboardController::class, 'index'])->name('dashboard');
    Route::post('/generate', [BillingDashboardController::class, 'generate'])->name('generate');

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
    Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
});

Route::post('/webhooks/payment/{provider}', PaymentWebhookController::class)
    ->where('provider', 'midtrans|xendit|tripay')
    ->name('webhooks.payment');
