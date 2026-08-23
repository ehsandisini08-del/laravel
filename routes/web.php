<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CpeController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GudangBarangController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\GudangKategoriController;
use App\Http\Controllers\GudangOpnameController;
use App\Http\Controllers\GudangRiwayatController;
use App\Http\Controllers\JobMonitorController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PppActiveController;
use App\Http\Controllers\PppProfileController;
use App\Http\Controllers\PppSecretController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UnlockAccountController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified', 'admin', 'installation'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('admin-area.restricted')->group(function () {
        Route::get('gudang/stok', [GudangController::class, 'stok'])->name('gudang.stok');
        Route::get('gudang/barang-masuk', [GudangController::class, 'barangMasuk'])->name('gudang.barang-masuk');
        Route::post('gudang/barang-masuk', [GudangController::class, 'storeBarangMasuk'])->name('gudang.barang-masuk.store');
        Route::get('gudang/barang-keluar', [GudangController::class, 'barangKeluar'])->name('gudang.barang-keluar');
        Route::post('gudang/barang-keluar', [GudangController::class, 'storeBarangKeluar'])->name('gudang.barang-keluar.store');
        Route::get('gudang/riwayat', [GudangRiwayatController::class, 'index'])->name('gudang.riwayat');

        Route::get('gudang/opname', [GudangOpnameController::class, 'index'])->name('gudang.opname.index');
        Route::get('gudang/opname/create', [GudangOpnameController::class, 'create'])->name('gudang.opname.create');
        Route::post('gudang/opname', [GudangOpnameController::class, 'store'])->name('gudang.opname.store');

        Route::resource('gudang/barang', GudangBarangController::class)->names('gudang.barang')->parameters(['barang' => 'item']);
        Route::resource('gudang/kategori', GudangKategoriController::class)->names('gudang.kategori')->parameters(['kategori' => 'category']);
    });

    Route::middleware('admin-area.restricted')->group(function () {
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
    });

    Route::get('cpes', [CpeController::class, 'index'])->name('cpes.index');
    Route::get('cpes/{cpe}', [CpeController::class, 'show'])->name('cpes.show');
    Route::post('cpes/sync', [CpeController::class, 'sync'])->name('cpes.sync');
    Route::post('cpes/{cpe}/refresh', [CpeController::class, 'refresh'])->name('cpes.refresh');
    Route::post('cpes/{cpe}/reboot', [CpeController::class, 'reboot'])->name('cpes.reboot');
    Route::put('cpes/{cpe}', [CpeController::class, 'update'])->name('cpes.update');

    Route::get('customers/import', [CustomerController::class, 'importForm'])->name('customers.import.form');
    Route::get('customers/import/template', [CustomerController::class, 'importTemplate'])->name('customers.import.template');
    Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');

    Route::delete('customers/bulk', [CustomerController::class, 'destroyMany'])->name('customers.destroy-many');
    Route::resource('customers', CustomerController::class);
    Route::get('customers/router/{router}/packages', [CustomerController::class, 'packagesByRouter'])->name('customers.packages-by-router');
    Route::get('customers/package/{package}/areas', [CustomerController::class, 'areasByPackage'])->name('customers.areas-by-package');
    Route::post('customers/{customer}/portal-password/send', [CustomerController::class, 'sendPortalPasswordViaWhatsApp'])->name('customers.portal-password.send');
    Route::post('customers/reconcile', [CustomerController::class, 'reconcile'])->name('customers.reconcile');
    Route::get('customers/odp/{odp}/available-ports', [CustomerController::class, 'availablePortsByOdp'])->name('customers.odp-available-ports');

    Route::resource('areas', AreaController::class);

    Route::get('settings', [SettingsController::class, 'index'])->middleware('developer')->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])->middleware('developer')->name('settings.update');

    Route::get('update', [UpdateController::class, 'index'])->middleware('developer')->name('update.index');
    Route::post('update', [UpdateController::class, 'run'])->middleware('developer')->name('update.run');
    Route::get('update/status', [UpdateController::class, 'status'])->middleware('developer')->name('update.status');

    Route::get('monitoring/jobs', [JobMonitorController::class, 'index'])->middleware('developer')->name('monitoring.jobs');
    Route::get('monitoring/jobs/status', [JobMonitorController::class, 'status'])->middleware('developer')->name('monitoring.jobs.status');

    Route::middleware('manage.users')->group(function () {
        Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');

        Route::get('unlock-accounts', [UnlockAccountController::class, 'index'])->name('unlock-accounts.index');
        Route::post('unlock-accounts/users/{user}', [UnlockAccountController::class, 'unlockUser'])->name('unlock-accounts.unlock-user');
        Route::post('unlock-accounts/customers/{customer}', [UnlockAccountController::class, 'unlockCustomer'])->name('unlock-accounts.unlock-customer');
    });
    Route::middleware('admin-area.restricted')->group(function () {
        Route::get('logs', [LogController::class, 'index'])->name('logs.index');
        Route::get('logs/{log}', [LogController::class, 'show'])->name('logs.show');
        Route::post('logs/export-csv', [LogController::class, 'exportCsv'])->name('logs.export-csv');
        Route::post('logs/export-excel', [LogController::class, 'exportExcel'])->name('logs.export-excel');
        Route::post('logs/export-pdf', [LogController::class, 'exportPdf'])->name('logs.export-pdf');
        Route::post('logs/clear', [LogController::class, 'clear'])->name('logs.clear');
        Route::get('backup', [BackupController::class, 'index'])->name('backup.index');
    });
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

require __DIR__.'/ftth.php';

require __DIR__.'/auth.php';
