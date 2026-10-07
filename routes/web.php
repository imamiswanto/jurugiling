<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\StockMovementController;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
->middleware('auth')
->name('dashboard');

Route::middleware('auth')->group(function () {
 
    Route::get('suppliers/trash', [SupplierController::class, 'trash'])
    ->name('suppliers.trash');
    
    Route::patch('suppliers/{id}/restore', [SupplierController::class, 'restore'])
    ->name('suppliers.restore');
    
    Route::delete('suppliers/{id}/force-delete', [SupplierController::class, 'forceDelete'])
    ->name('suppliers.force-delete');

    Route::get('stock-movements', [StockMovementController::class, 'index'])
    ->name('stock-movements.index');
    
    Route::resource('suppliers', SupplierController::class);
    Route::resource('products', ProductController::class);
    Route::resource('purchases', PurchaseController::class);
    Route::resource('sales', SaleController::class);
});

Route::resource('categories', CategoryController::class)
    ->middleware('auth');

require __DIR__.'/auth.php';
