<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\GeoController;

/**
 * SEO & Geolocation Routes
 */

// Sitemaps
// Named api.sitemap.index, not sitemap.index — modules/Core/Routes/web.php
// already claims that name for its own (web-facing) sitemap.xml route, and
// route:cache refuses to serialize two routes sharing one name.
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('api.sitemap.index');
Route::get('sitemap-tours.xml', [SitemapController::class, 'tours'])->name('sitemap.tours');
Route::get('sitemap-hotels.xml', [SitemapController::class, 'hotels'])->name('sitemap.hotels');
Route::get('sitemap-destinations.xml', [SitemapController::class, 'destinations'])->name('sitemap.destinations');

// Geolocation
Route::group(['prefix' => 'geo'], function () {
    Route::get('nearby', [GeoController::class, 'nearby']); // ?lat=&lng=&radius=&type=
    Route::get('detect', [GeoController::class, 'detect']); // Detect user location from IP
});
