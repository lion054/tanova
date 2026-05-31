<?php

use Illuminate\Support\Facades\Route;
use Pro\Concierge\Controllers\Api\ConciergeApiController;

Route::group([
    'prefix'     => 'concierge',
    'middleware' => ['auth:sanctum'],
    'as'         => 'api.concierge.',
], function () {
    Route::get('/health',                             [ConciergeApiController::class, 'health'])->name('health');
    Route::get('/conversations',                      [ConciergeApiController::class, 'index'])->name('index');
    Route::post('/conversations',                     [ConciergeApiController::class, 'start'])->name('start');
    Route::get('/conversations/{conversation}',       [ConciergeApiController::class, 'show'])->name('show');
    Route::post('/conversations/{conversation}/send', [ConciergeApiController::class, 'sendMessage'])->name('send');
});
