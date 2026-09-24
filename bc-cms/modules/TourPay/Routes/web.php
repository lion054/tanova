<?php
use Illuminate\Support\Facades\Route;

// Public pay page — no auth required
Route::get('/tourpay/pay/{token}', 'InvoiceController@publicPay')->name('tourpay.pay');
Route::post('/tourpay/pay/{token}/{answer}', 'InvoiceController@answerQuotation')->whereIn('answer', ['accept', 'decline'])->middleware('throttle:20,1')->name('tourpay.answer');

Route::post('/tourpay/pay/{token}/online', 'InvoiceController@payOnline')->middleware('throttle:20,1')->name('tourpay.online');
Route::get('/tourpay/pay/{token}/return/{gateway}', 'InvoiceController@payReturn')->whereIn('gateway', ['stripe', 'paypal', 'paystack', 'paynow', 'pesapal', 'selcom'])->name('tourpay.return');
Route::match(['get', 'post'], '/tourpay/notify/{gateway}', 'InvoiceController@notify')->whereIn('gateway', ['stripe', 'paypal', 'paystack', 'paynow', 'pesapal', 'selcom'])->middleware('throttle:120,1')->name('tourpay.notify');
Route::post('/tourpay/pay/{token}/transfer', 'InvoiceController@reportTransfer')->middleware('throttle:10,1')->name('tourpay.transfer');

// Vendor routes — auth required
Route::group(['prefix' => 'user/tourpay', 'middleware' => ['auth', 'verified']], function () {
    Route::get('/',                    'InvoiceController@index')->name('tourpay.vendor.index');
    Route::get('/create',              'InvoiceController@create')->name('tourpay.vendor.create');
    Route::get('/bills',               'BillController@index')->name('tourpay.vendor.bills');
    Route::post('/bills',              'BillController@store')->name('tourpay.vendor.bills.store');
    Route::put('/bills/{id}',          'BillController@update')->name('tourpay.vendor.bills.update');
    Route::post('/bills/{id}/payments', 'BillController@addPayment')->name('tourpay.vendor.bills.payments');
    Route::post('/bills/{id}/void',    'BillController@void')->name('tourpay.vendor.bills.void');
    Route::delete('/bills/{id}',       'BillController@delete')->name('tourpay.vendor.bills.delete');
    Route::get('/suggest/{what}',      'InvoiceController@suggest')->whereIn('what', ['customers', 'services'])->middleware('throttle:120,1')->name('tourpay.vendor.suggest');
    Route::get('/statement',           'StatementController@index')->name('tourpay.vendor.statement');
    Route::get('/statement.csv',       'StatementController@csv')->name('tourpay.vendor.statement.csv');
    Route::get('/reports',             'ReportController@index')->name('tourpay.vendor.reports');
    Route::get('/reports/{report}.csv', 'ReportController@csv')->whereIn('report', ['receivables', 'revenue', 'tax', 'profit', 'statement'])->name('tourpay.vendor.reports.csv');
    Route::get('/edit/{id}',           'InvoiceController@edit')->name('tourpay.vendor.edit');
    Route::post('/store/{id}',         'InvoiceController@store')->name('tourpay.vendor.store');
    Route::get('/del/{id}',            'InvoiceController@delete')->name('tourpay.vendor.delete');
    Route::get('/{id}/view',           'InvoiceController@view')->name('tourpay.vendor.view');
    Route::get('/{id}/pdf',            'InvoiceController@pdf')->name('tourpay.vendor.pdf');
    Route::post('/{id}/send-email',    'InvoiceController@sendEmail')->name('tourpay.vendor.send-email');
    Route::post('/{id}/send-whatsapp',  'InvoiceController@sendWhatsApp')->name('tourpay.vendor.send-whatsapp');
    Route::get('/{id}/link',            'InvoiceController@shareLink')->name('tourpay.vendor.share-link');
    Route::post('/{id}/mark-paid',         'InvoiceController@markPaid')->name('tourpay.vendor.mark-paid');
    Route::post('/{id}/payments',          'InvoiceController@addPayment')->name('tourpay.vendor.payments.add');
    Route::delete('/{id}/payments/{paymentId}', 'InvoiceController@deletePayment')->name('tourpay.vendor.payments.delete');
    Route::post('/{id}/credit-note',       'InvoiceController@creditNote')->name('tourpay.vendor.credit-note');
    Route::post('/{id}/refund',            'InvoiceController@refund')->name('tourpay.vendor.refund');
    Route::post('/{id}/void',              'InvoiceController@void')->name('tourpay.vendor.void');
    Route::post('/{id}/issue',             'InvoiceController@issue')->name('tourpay.vendor.issue');
    Route::post('/{id}/duplicate',         'InvoiceController@duplicate')->name('tourpay.vendor.duplicate');
    Route::post('/{id}/convert',           'InvoiceController@convert')->name('tourpay.vendor.convert');
    Route::get('/settings',                'InvoiceController@settingsPage')->name('tourpay.vendor.settings.page');
    Route::post('/settings',               'InvoiceController@saveSettings')->name('tourpay.vendor.settings');
    Route::post('/settings/gateways/{gateway}/test', 'InvoiceController@testGateway')->name('tourpay.vendor.gateways.test');
    Route::post('/{id}/payments/{paymentId}/approve', 'InvoiceController@approvePayment')->name('tourpay.vendor.payments.approve');
    Route::post('/{id}/payments/{paymentId}/reject',  'InvoiceController@rejectPayment')->name('tourpay.vendor.payments.reject');
    Route::get('/{id}/payments/{paymentId}/proof',    'InvoiceController@proof')->name('tourpay.vendor.payments.proof');
    Route::post('/{id}/schedule',          'InvoiceController@setSchedule')->name('tourpay.vendor.schedule');
    Route::post('/from-booking/{booking}', 'InvoiceController@createFromBooking')->name('tourpay.vendor.from-booking');
});
