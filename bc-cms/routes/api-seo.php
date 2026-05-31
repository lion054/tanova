<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\GeoController;

/**
 * SEO & Geolocation Routes
 */

// Sitemaps
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('sitemap-tours.xml', [SitemapController::class, 'tours'])->name('sitemap.tours');
Route::get('sitemap-hotels.xml', [SitemapController::class, 'hotels'])->name('sitemap.hotels');
Route::get('sitemap-destinations.xml', [SitemapController::class, 'destinations'])->name('sitemap.destinations');

// Geolocation
Route::group(['prefix' => 'geo'], function () {
    Route::get('nearby', [GeoController::class, 'nearby']); // ?lat=&lng=&radius=&type=
    Route::get('detect', [GeoController::class, 'detect']); // Detect user location from IP
});
