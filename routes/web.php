<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ComboController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('categories', CategoryController::class);
    Route::resource('products', ProductController::class);
    Route::resource('combos', ComboController::class);
    Route::resource('clients', ClientController::class);
    Route::resource('providers', ProviderController::class);
    Route::resource('sales', SaleController::class)->except(['edit', 'update']);
    Route::get('sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
    Route::get('sales/{sale}/receipt.pdf', [SaleController::class, 'receiptPdf'])->name('sales.receipt.pdf');
    Route::resource('users', UserController::class);

    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('inventory', [InventoryController::class, 'store'])->name('inventory.store');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/products', [ReportController::class, 'products'])->name('reports.products');
    Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
    Route::get('reports/clients', [ReportController::class, 'clients'])->name('reports.clients');
    Route::get('reports/providers', [ReportController::class, 'providers'])->name('reports.providers');

    Route::put('products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');
    Route::put('combos/{combo}/toggle', [ComboController::class, 'toggle'])->name('combos.toggle');
});
