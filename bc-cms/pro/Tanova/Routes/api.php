<?php

use Illuminate\Support\Facades\Route;
use Pro\Tanova\Controllers\Api\TanovaApiController;

Route::group([
    'prefix'     => 'tanova',
    'middleware' => ['auth:sanctum'],
    'as'         => 'api.tanova.',
], function () {
    Route::get('/trips',                    [TanovaApiController::class, 'index'])->name('index');
    Route::get('/trips/{trip}',             [TanovaApiController::class, 'show'])->name('show');
    Route::get('/trips/{trip}/replan',      [TanovaApiController::class, 'replan'])->name('replan');
    Route::post('/generate',                [TanovaApiController::class, 'generate'])->name('generate');
});
