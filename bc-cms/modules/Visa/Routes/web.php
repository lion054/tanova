<?php

namespace Modules\Visa\Routes;

use Illuminate\Support\Facades\Route;
use Modules\Visa\Pages\VisaPage;
use Modules\Visa\Pages\DetailPage;
use Modules\Visa\Pages\ApplicationsForm;
use Modules\Visa\Pages\User\VisaBookingDetailPage;




// Visa
Route::group(['prefix' => config('visa.visa_route_prefix')], function () {
    Route::get('/', VisaPage::class)->name('visa.search');
    Route::get('/{slug}', DetailPage::class)->name('visa.detail');
    Route::get('/{slug}/applications/{code}', ApplicationsForm::class)->name('visa.applications');
});

//  Related to user
Route::group([
    'prefix' => 'user/' . config('visa.visa_route_prefix'),
    'middleware' => ['auth'],
], function () {
    Route::get('/booking/{code}', VisaBookingDetailPage::class)->name('visa.user.booking-detail');
});

// Vendor management
Route::group([
    'prefix'     => 'user/' . config('visa.visa_route_prefix'),
    'middleware' => ['auth', 'verified'],
    'namespace'  => 'Modules\Visa\Controllers',
], function () {
    Route::get('/',                              'ManageVisaController@index')->name('visa.vendor.index');
    Route::get('/create',                        'ManageVisaController@create')->name('visa.vendor.create');
    Route::get('/edit/{id}',                     'ManageVisaController@edit')->name('visa.vendor.edit');
    Route::post('/store/{id}',                   'ManageVisaController@store')->name('visa.vendor.store');
    Route::get('/del/{id}',                      'ManageVisaController@delete')->name('visa.vendor.delete');
    Route::get('/bulkEdit/{id}',                 'ManageVisaController@bulkEdit')->name('visa.vendor.bulk_edit');
    Route::get('/booking-report/bulkEdit/{id}',  'ManageVisaController@bookingReportBulkEdit')->name('visa.vendor.booking_report.bulk_edit');
    Route::get('/recovery',                      'ManageVisaController@recovery')->name('visa.vendor.recovery');
    Route::get('/restore/{id}',                  'ManageVisaController@restore')->name('visa.vendor.restore');
});
