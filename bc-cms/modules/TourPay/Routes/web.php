<?php
use Illuminate\Support\Facades\Route;

// Public payment page — no auth required
Route::get('/tourpay/pay/{token}', 'InvoiceController@publicPay')->name('tourpay.pay');

// Vendor routes — auth required
Route::group(['prefix' => 'user/tourpay', 'middleware' => ['auth', 'verified']], function () {
    Route::get('/',                    'InvoiceController@index')->name('tourpay.vendor.index');
    Route::get('/create',              'InvoiceController@create')->name('tourpay.vendor.create');
    Route::get('/edit/{id}',           'InvoiceController@edit')->name('tourpay.vendor.edit');
    Route::post('/store/{id}',         'InvoiceController@store')->name('tourpay.vendor.store');
    Route::get('/del/{id}',            'InvoiceController@delete')->name('tourpay.vendor.delete');
    Route::get('/{id}/view',           'InvoiceController@view')->name('tourpay.vendor.view');
    Route::get('/{id}/pdf',            'InvoiceController@pdf')->name('tourpay.vendor.pdf');
    Route::post('/{id}/send-email',    'InvoiceController@sendEmail')->name('tourpay.vendor.send-email');
    Route::post('/{id}/send-whatsapp',  'InvoiceController@sendWhatsApp')->name('tourpay.vendor.send-whatsapp');
    Route::get('/{id}/link',            'InvoiceController@shareLink')->name('tourpay.vendor.share-link');
    Route::post('/{id}/mark-paid',         'InvoiceController@markPaid')->name('tourpay.vendor.mark-paid');
    Route::post('/from-booking/{booking}', 'InvoiceController@createFromBooking')->name('tourpay.vendor.from-booking');
});
