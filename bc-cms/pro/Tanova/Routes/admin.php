<?php

use Illuminate\Support\Facades\Route;
use Pro\Tanova\Controllers\TanovaAdminController;

// Required by global-script.blade.php: bookingCore.module.tanova_trip = route('tanova_trip.search')
Route::get('/user/tanova/search', [TanovaAdminController::class, 'index'])
    ->middleware(['web', 'auth', 'verified'])
    ->name('tanova_trip.search');

Route::group([
    'prefix'     => 'user/tanova',
    'middleware' => ['web', 'auth', 'verified'],
    'as'         => 'admin.tanova.',
], function () {
    Route::get('/',                           [TanovaAdminController::class, 'index'])->name('index');
    Route::get('/{trip}',                     [TanovaAdminController::class, 'show'])->name('show');
    Route::post('/generate',                  [TanovaAdminController::class, 'generate'])->name('generate');
    Route::post('/{trip}/move',               [TanovaAdminController::class, 'moveToBookings'])->name('move');
    Route::post('/expire-old',                [TanovaAdminController::class, 'expireOld'])->name('expire-old');
    Route::get('/{trip}/activities',          [TanovaAdminController::class, 'searchActivities'])->name('activities');
    Route::patch('/{trip}/package/{pkg}',     [TanovaAdminController::class, 'savePackage'])->name('savePackage');
    Route::post('/{trip}/invoice',            [TanovaAdminController::class, 'createInvoice'])->name('createInvoice');
});
