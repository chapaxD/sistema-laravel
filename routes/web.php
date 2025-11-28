<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\PaymentPlanController;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/', [AuthenticatedSessionController::class, 'store']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Work Orders
    Route::resource('work-orders', WorkOrderController::class);
    
    // Users Module (Users & Roles) - solo Administrador
    Route::prefix('users-module')->name('users-module.')->middleware('role:Administrador')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('roles', RolController::class);
    });
    
    // Inventory Module (Products & Categories)
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::resource('products', ProductController::class);
        Route::resource('categories', CategoriaController::class);
    });
    
    // Services
    Route::resource('services', ServiceController::class);
    
    // Quotations
    Route::resource('quotations', QuotationController::class);
    Route::post('quotations/{id}/generate-order', [QuotationController::class, 'generateOrder'])
        ->name('quotations.generate-order');
    Route::get('quotations/{id}/pdf', [QuotationController::class, 'generatePDF'])->name('quotations.pdf');
    Route::get('quotations/{id}/view-pdf', [QuotationController::class, 'viewPDF'])->name('quotations.view-pdf');
    
    // Sales
    Route::resource('sales', SaleController::class);
    Route::post('sales/store-from-order/{id}', [SaleController::class, 'storeFromOrder'])
        ->name('sales.store-from-order');
    
    // Receipts
    Route::resource('receipts', ReceiptController::class);
    Route::post('receipts/store-from-sale/{id}', [ReceiptController::class, 'storeFromSale'])
        ->name('receipts.store-from-sale');
    // PDF
    Route::get('receipts/{id}/pdf', [ReceiptController::class, 'generatePDF'])->name('receipts.pdf');
    Route::get('receipts/{id}/view-pdf', [ReceiptController::class, 'viewPDF'])->name('receipts.view-pdf');
    
    // Payment Plans
    Route::resource('payment-plans', PaymentPlanController::class);
    Route::post('payment-plans/store-from-sale/{id}', [PaymentPlanController::class, 'storeFromSale'])
        ->name('payment-plans.store-from-sale');
    Route::post('payment-plans/{id}/pay-installment', [PaymentPlanController::class, 'payInstallment'])
        ->name('payment-plans.pay-installment');
});
