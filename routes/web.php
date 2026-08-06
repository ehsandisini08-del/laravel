<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PppActiveController;
use App\Http\Controllers\PppProfileController;
use App\Http\Controllers\PppSecretController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('routers', RouterController::class);
    Route::post('routers/{router}/test-connection', [RouterController::class, 'testConnection'])->name('routers.test-connection');
    Route::post('routers/{router}/sync', [RouterController::class, 'sync'])->name('routers.sync');
    Route::post('routers/bulk-delete', [RouterController::class, 'bulkDelete'])->name('routers.bulk-delete');
    Route::post('routers/bulk-enable', [RouterController::class, 'bulkEnable'])->name('routers.bulk-enable');
    Route::post('routers/bulk-disable', [RouterController::class, 'bulkDisable'])->name('routers.bulk-disable');

    Route::resource('ppp-secrets', PppSecretController::class);
    Route::post('ppp-secrets/{pppSecret}/enable', [PppSecretController::class, 'enable'])->name('ppp-secrets.enable');
    Route::post('ppp-secrets/{pppSecret}/disable', [PppSecretController::class, 'disable'])->name('ppp-secrets.disable');
    Route::post('ppp-secrets/sync', [PppSecretController::class, 'sync'])->name('ppp-secrets.sync');
    Route::post('ppp-secrets/bulk-delete', [PppSecretController::class, 'bulkDelete'])->name('ppp-secrets.bulk-delete');
    Route::post('ppp-secrets/bulk-enable', [PppSecretController::class, 'bulkEnable'])->name('ppp-secrets.bulk-enable');
    Route::post('ppp-secrets/bulk-disable', [PppSecretController::class, 'bulkDisable'])->name('ppp-secrets.bulk-disable');

    Route::resource('ppp-profiles', PppProfileController::class);
    Route::post('ppp-profiles/sync', [PppProfileController::class, 'sync'])->name('ppp-profiles.sync');

    Route::get('ppp-active', [PppActiveController::class, 'index'])->name('ppp-active.index');
    Route::get('ppp-active/fetch', [PppActiveController::class, 'fetch'])->name('ppp-active.fetch');
    Route::get('ppp-active/{userId}', [PppActiveController::class, 'show'])->name('ppp-active.show');
    Route::post('ppp-active/disconnect', [PppActiveController::class, 'disconnect'])->name('ppp-active.disconnect');
    Route::post('ppp-active/bulk-disconnect', [PppActiveController::class, 'bulkDisconnect'])->name('ppp-active.bulk-disconnect');

    Route::get('customers/import', [CustomerController::class, 'importForm'])->name('customers.import.form');
    Route::get('customers/import/template', [CustomerController::class, 'importTemplate'])->name('customers.import.template');
    Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');

    Route::resource('customers', CustomerController::class);
    Route::get('customers/router/{router}/packages', [CustomerController::class, 'packagesByRouter'])->name('customers.packages-by-router');
    Route::get('customers/package/{package}/areas', [CustomerController::class, 'areasByPackage'])->name('customers.areas-by-package');
    Route::post('customers/{customer}/portal-password/send', [CustomerController::class, 'sendPortalPasswordViaWhatsApp'])->name('customers.portal-password.send');
    Route::post('customers/reconcile', [CustomerController::class, 'reconcile'])->name('customers.reconcile');

    Route::resource('areas', AreaController::class);

    Route::get('settings', [SettingsController::class, 'index'])->middleware('developer')->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])->middleware('developer')->name('settings.update');

    Route::get('update', [UpdateController::class, 'index'])->middleware('developer')->name('update.index');
    Route::post('update', [UpdateController::class, 'run'])->middleware('developer')->name('update.run');
    Route::get('update/status', [UpdateController::class, 'status'])->middleware('developer')->name('update.status');

    Route::middleware('manage.users')->group(function () {
        Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    });
    Route::get('logs', [LogController::class, 'index'])->name('logs.index');
    Route::get('logs/{log}', [LogController::class, 'show'])->name('logs.show');
    Route::post('logs/export-csv', [LogController::class, 'exportCsv'])->name('logs.export-csv');
    Route::post('logs/export-excel', [LogController::class, 'exportExcel'])->name('logs.export-excel');
    Route::post('logs/export-pdf', [LogController::class, 'exportPdf'])->name('logs.export-pdf');
    Route::post('logs/clear', [LogController::class, 'clear'])->name('logs.clear');
    Route::get('backup', [BackupController::class, 'index'])->name('backup.index');
    Route::resource('packages', PackageController::class);
    Route::get('packages/router/{router}/profiles', [PackageController::class, 'profilesByRouter'])->name('packages.profiles-by-router');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/whatsapp.php';

require __DIR__.'/billing.php';

require __DIR__.'/portal.php';

require __DIR__.'/mobile.php';

require __DIR__.'/auth.php';
