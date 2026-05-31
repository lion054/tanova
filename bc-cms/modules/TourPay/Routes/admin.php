<?php
use Illuminate\Support\Facades\Route;

Route::get('/', 'InvoiceController@index')->name('tourpay.admin.index');
Route::get('/view/{id}', 'InvoiceController@view')->name('tourpay.admin.view');
