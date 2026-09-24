<?php

use Illuminate\Support\Facades\Route;
use Pro\Integrations\Controllers\OperatorPortalController;

/*
|--------------------------------------------------------------------------
| Integrations — vendor portal routes
|--------------------------------------------------------------------------
| Supplier directory. Kept in pro/ so core never depends on an optional module.
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'vendor', 'middleware' => ['web', 'auth']], function () {

    Route::get('/operators',              [OperatorPortalController::class, 'index'])->name('vendor.operators.index');
    Route::post('/operators',             [OperatorPortalController::class, 'store'])->name('vendor.operators.store');
    Route::get('/operators/{operator}',   [OperatorPortalController::class, 'show'])->name('vendor.operators.show');
    Route::put('/operators/{operator}',   [OperatorPortalController::class, 'update'])->name('vendor.operators.update');
    Route::delete('/operators/{operator}', [OperatorPortalController::class, 'destroy'])->name('vendor.operators.destroy');

    Route::post('/operators/{operator}/routes',          [OperatorPortalController::class, 'addRoute'])->name('vendor.operators.routes.add');
    Route::delete('/operators/{operator}/routes/{route}', [OperatorPortalController::class, 'deleteRoute'])->name('vendor.operators.routes.delete');

    Route::post('/operators/{operator}/fares',         [OperatorPortalController::class, 'addFare'])->name('vendor.operators.fares.add');
    Route::delete('/operators/{operator}/fares/{fare}', [OperatorPortalController::class, 'deleteFare'])->name('vendor.operators.fares.delete');
});
