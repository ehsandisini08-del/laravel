<?php

use App\Http\Controllers\Ftth\FiberLineController;
use App\Http\Controllers\Ftth\FtthApiController;
use App\Http\Controllers\Ftth\FtthMapController;
use App\Http\Controllers\Ftth\OdcController;
use App\Http\Controllers\Ftth\OdpController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin', 'installation'])->prefix('ftth')->name('ftth.')->group(function () {
    // Map utama
    Route::get('/map', [FtthMapController::class, 'index'])->name('map');

    // ODC CRUD
    Route::resource('odc', OdcController::class)->names('odc');

    // ODP CRUD
    Route::resource('odp', OdpController::class)->names('odp');

    // Jalur Fiber CRUD
    Route::resource('fiber', FiberLineController::class)->names('fiber');

    // API endpoints untuk data map (dalam auth group agar aman)
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/stats', [FtthApiController::class, 'stats'])->name('stats');
        Route::get('/odcs', [FtthApiController::class, 'odcs'])->name('odcs');
        Route::get('/odps', [FtthApiController::class, 'odps'])->name('odps');
        Route::get('/customers', [FtthApiController::class, 'customers'])->name('customers');
        Route::get('/fibers', [FtthApiController::class, 'fibers'])->name('fibers');
        Route::get('/search', [FtthApiController::class, 'search'])->name('search');
    });
});
