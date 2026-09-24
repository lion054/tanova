<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['web'])->namespace('\App\Http\Controllers')->group(function () {

    // For uptime monitors: 200 when the database, disk and scheduler are all fine, 503 when any is not.
    Route::get('/health', function () {
        $h = \App\Support\Health::run();

        return response()->json($h, $h['ok'] ? 200 : 503)->header('Cache-Control', 'no-store');
    })->name('health');

    Route::get('/intro', fn() => redirect('/admin'));
    Route::get('/', fn() => redirect('/admin'));
    Route::get('/home', fn() => redirect('/admin'))->name('home');
    Route::post('/install/check-db', 'HomeController@checkConnectDatabase');

    // Social Login
    Route::get('social-login/{provider}', 'Auth\LoginController@socialLogin');
    Route::get('social-callback/{provider}', 'Auth\LoginController@socialCallBack');

    Route::get('social-callback/{provider}/success', 'Auth\LoginController@emptyWithToken')->name('social.callback.token'); // For API only

    // Logs
    Route::get(config('admin.admin_route_prefix') . '/logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index')->middleware(['auth', 'dashboard', 'system_log_view'])->name('admin.logs');

    Route::get('/install', 'InstallerController@redirectToRequirement')->name('LaravelInstaller::welcome');
    Route::get('/install/environment', 'InstallerController@redirectToWizard')->name('LaravelInstaller::environment');
    Route::fallback([\Modules\Core\Controllers\FallbackController::class, 'FallBack']);

    // Hide page update default
    Route::get('/update', 'InstallerController@redirectToHome');
    Route::get('/update/overview', 'InstallerController@redirectToHome');
    Route::get('/update/database', 'InstallerController@redirectToHome');
});

