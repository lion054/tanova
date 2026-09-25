<?php
use Illuminate\Support\Facades\Route;
use Modules\TourPay\Admin\InvoiceController;

// Staff view of every business's TourPay documents. Read-only: invoices are created and changed by the business that owns them.
Route::get('/',             [InvoiceController::class, 'index'])->name('tourpay.admin.index');
Route::get('/view/{id}',    [InvoiceController::class, 'view'])->name('tourpay.admin.view');
