<?php

use Illuminate\Support\Facades\Route;
use Pro\Integrations\Controllers\IntegrationsAdminController;

Route::group([
    'prefix'     => 'admin/integrations',
    'middleware' => ['web', 'auth', 'verified'],
    'as'         => 'admin.integrations.',
], function () {
    // Hub
    Route::get('/',                           [IntegrationsAdminController::class, 'hub'])->name('hub');

    // OS category pages
    Route::get('/category/{cat}',             [IntegrationsAdminController::class, 'category'])->name('category');

    // Connect / disconnect / test
    Route::post('/{slug}/connect',            [IntegrationsAdminController::class, 'connect'])->name('connect');
    Route::post('/{slug}/disconnect',         [IntegrationsAdminController::class, 'disconnect'])->name('disconnect');
    Route::post('/{slug}/test',               [IntegrationsAdminController::class, 'test'])->name('test');

    // Wetu-specific
    Route::get('/wetu/itineraries',           [IntegrationsAdminController::class, 'wetuItineraries'])->name('wetu.itineraries');
    Route::post('/wetu/sync',                 [IntegrationsAdminController::class, 'wetuSync'])->name('wetu.sync');
    Route::post('/wetu/import/{identifier}',  [IntegrationsAdminController::class, 'wetuImport'])->name('wetu.import');

    // Legals
    Route::get('/legals',                     [IntegrationsAdminController::class, 'legals'])->name('legals');
    Route::get('/legals/{doc}',               [IntegrationsAdminController::class, 'legalEdit'])->name('legals.edit');
    Route::post('/legals/{doc}',              [IntegrationsAdminController::class, 'legalSave'])->name('legals.save');
});
