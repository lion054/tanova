<?php

use Illuminate\Support\Facades\Route;
use Pro\Tanova\Controllers\AiPlanSettingsController;
use Pro\Tanova\Controllers\CatalogPortalController;
use Pro\Tanova\Controllers\ItineraryBuilderController;
use Pro\Tanova\Controllers\MealPortalController;
use Pro\Tanova\Controllers\RestaurantPortalController;

/*
|--------------------------------------------------------------------------
| Tanova — vendor portal routes
|--------------------------------------------------------------------------
| Portal screens for Tanova-owned catalog data. Kept here rather than in
| modules/Vendor so that core never depends on an optional pro module.
| Controllers are referenced by FQCN because this file is outside the
| Modules\Vendor\Controllers namespace group.
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'vendor', 'middleware' => ['web', 'auth']], function () {

    // ── Meals catalog ────────────────────────────────────────────────────
    Route::get('/meals',           [MealPortalController::class, 'index'])->name('vendor.meals.index');
    Route::post('/meals',          [MealPortalController::class, 'store'])->name('vendor.meals.store');
    Route::put('/meals/{meal}',    [MealPortalController::class, 'update'])->name('vendor.meals.update');
    Route::delete('/meals/{meal}', [MealPortalController::class, 'destroy'])->name('vendor.meals.destroy');

    // ── Restaurant partners ──────────────────────────────────────────────
    Route::get('/restaurants',                 [RestaurantPortalController::class, 'index'])->name('vendor.restaurants.index');
    Route::post('/restaurants',                [RestaurantPortalController::class, 'store'])->name('vendor.restaurants.store');
    Route::put('/restaurants/{restaurant}',    [RestaurantPortalController::class, 'update'])->name('vendor.restaurants.update');
    Route::delete('/restaurants/{restaurant}', [RestaurantPortalController::class, 'destroy'])->name('vendor.restaurants.destroy');

    // ── Unified catalog overview (read-only) ─────────────────────────────
    Route::get('/catalogs', [CatalogPortalController::class, 'index'])->name('vendor.catalogs.index');

    // ── Itinerary builder ────────────────────────────────────────────────
    Route::get('/itineraries',                    [ItineraryBuilderController::class, 'index'])->name('vendor.itineraries.index');
    Route::post('/itineraries',                   [ItineraryBuilderController::class, 'store'])->name('vendor.itineraries.store');
    Route::post('/itineraries/import',            [ItineraryBuilderController::class, 'import'])->name('vendor.itineraries.import');
    Route::get('/itineraries/{itinerary}',        [ItineraryBuilderController::class, 'edit'])->name('vendor.itineraries.edit');
    Route::put('/itineraries/{itinerary}',        [ItineraryBuilderController::class, 'update'])->name('vendor.itineraries.update');
    Route::delete('/itineraries/{itinerary}',     [ItineraryBuilderController::class, 'destroy'])->name('vendor.itineraries.destroy');
    Route::post('/itineraries/{itinerary}/days',  [ItineraryBuilderController::class, 'addDay'])->name('vendor.itineraries.days.add');
    Route::put('/itineraries/{itinerary}/days/{day}',    [ItineraryBuilderController::class, 'saveDay'])->name('vendor.itineraries.days.save');
    Route::delete('/itineraries/{itinerary}/days/{day}', [ItineraryBuilderController::class, 'deleteDay'])->name('vendor.itineraries.days.delete');

    // ── AI planning preferences ──────────────────────────────────────────
    Route::get('/ai-plan', [AiPlanSettingsController::class, 'edit'])->name('vendor.ai_plan.edit');
    Route::put('/ai-plan', [AiPlanSettingsController::class, 'update'])->name('vendor.ai_plan.update');
});
