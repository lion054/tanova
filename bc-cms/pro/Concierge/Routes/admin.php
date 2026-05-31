<?php

use Illuminate\Support\Facades\Route;
use Pro\Concierge\Controllers\ConciergeAdminController;

Route::group([
    'prefix'     => 'user/concierge',
    'middleware' => ['web', 'auth', 'verified'],
    'as'         => 'admin.concierge.',
], function () {
    Route::get('/',                             [ConciergeAdminController::class, 'index'])->name('index');
    Route::get('/create',                       [ConciergeAdminController::class, 'create'])->name('create');
    Route::post('/',                            [ConciergeAdminController::class, 'store'])->name('store');
    Route::get('/{conversation}',               [ConciergeAdminController::class, 'show'])->name('show');
    Route::post('/{conversation}/reply',        [ConciergeAdminController::class, 'reply'])->name('reply');
    Route::post('/{conversation}/approve-ai',   [ConciergeAdminController::class, 'approveAiReply'])->name('approve-ai');
    Route::post('/{conversation}/resolve',      [ConciergeAdminController::class, 'resolve'])->name('resolve');
    Route::post('/{conversation}/escalate',     [ConciergeAdminController::class, 'escalate'])->name('escalate');
});
