<?php

use App\Http\Controllers\BusinessAdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ComboController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(['role:dueno,admin,cajero,superadmin', 'business.context'])
        ->name('dashboard');

    Route::prefix('superadmin')->name('superadmin.')->middleware('role:superadmin')->group(function (): void {
        Route::get('/businesses', [BusinessAdminController::class, 'index'])->name('businesses.index');
        Route::get('/businesses/create', [BusinessAdminController::class, 'create'])->name('businesses.create');
        Route::post('/businesses', [BusinessAdminController::class, 'store'])->name('businesses.store');
        Route::get('/businesses/{business}/edit', [BusinessAdminController::class, 'edit'])->name('businesses.edit');
        Route::put('/businesses/{business}', [BusinessAdminController::class, 'update'])->name('businesses.update');
        Route::put('/businesses/{business}/toggle', [BusinessAdminController::class, 'toggle'])->name('businesses.toggle');
        Route::post('/businesses/{business}/select', [BusinessAdminController::class, 'select'])->name('businesses.select');
        Route::get('/businesses/{business}/users', [BusinessAdminController::class, 'users'])->name('businesses.users');
        Route::put('/businesses/{business}/users/{user}/toggle', [BusinessAdminController::class, 'toggleUser'])->name('businesses.users.toggle');
    });

    Route::middleware(['role:dueno,admin,cajero,superadmin', 'business.context'])->group(function (): void {
        Route::get('/prices', [ProductController::class, 'priceLookup'])->name('prices.index');
        Route::get('sales/products/search', [SaleController::class, 'productSearch'])->name('sales.products.search');
        Route::get('sales/clients/search', [SaleController::class, 'clientSearch'])->name('sales.clients.search');
        Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show']);
        Route::get('sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
        Route::get('sales/{sale}/receipt.pdf', [SaleController::class, 'receiptPdf'])->name('sales.receipt.pdf');
        Route::delete('sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');

        Route::middleware('role:dueno,admin,cajero,superadmin')->group(function (): void {
            Route::get('/receivables', [ReceivableController::class, 'index'])->name('receivables.index');
            Route::get('/receivables/{receivable}', [ReceivableController::class, 'show'])->name('receivables.show');
            Route::post('/receivables/{receivable}/payments', [ReceivableController::class, 'storePayment'])->name('receivables.payments.store');
        });

        Route::middleware('role:dueno,admin,superadmin')->delete(
            '/receivables/{receivable}/payments/{payment}',
            [ReceivableController::class, 'reversePayment']
        )->name('receivables.payments.reverse');

        Route::middleware('role:dueno,admin,superadmin')->group(function (): void {
            Route::get('/business/settings', [BusinessAdminController::class, 'editSettings'])->name('business.settings.edit');
            Route::put('/business/settings', [BusinessAdminController::class, 'updateSettings'])->name('business.settings.update');

            Route::get('purchases/products/search', [PurchaseController::class, 'productSearch'])->name('purchases.products.search');
            Route::resource('categories', CategoryController::class);
            Route::put('categories/{category}/toggle', [CategoryController::class, 'toggle'])->name('categories.toggle');
            Route::get('products/search', [ProductController::class, 'search'])->name('products.search');
            Route::resource('products', ProductController::class);
            Route::resource('combos', ComboController::class);
            Route::resource('clients', ClientController::class);
            Route::resource('providers', ProviderController::class);
            Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);
            Route::resource('recipes', RecipeController::class)->only(['index', 'create', 'store', 'edit', 'update']);
            Route::get('productions/recipes/search', [ProductionController::class, 'recipeSearch'])->name('productions.recipes.search');
            Route::resource('productions', ProductionController::class)->only(['index', 'create', 'store', 'show']);
            Route::resource('users', UserController::class);

            Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
            Route::post('inventory', [InventoryController::class, 'store'])->name('inventory.store');

            Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/products', [ReportController::class, 'products'])->name('reports.products');
            Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
            Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
            Route::get('reports/clients', [ReportController::class, 'clients'])->name('reports.clients');
            Route::get('reports/providers', [ReportController::class, 'providers'])->name('reports.providers');
            Route::get('reports/profitability', [ReportController::class, 'profitability'])->name('reports.profitability');

            Route::put('products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');
            Route::put('combos/{combo}/toggle', [ComboController::class, 'toggle'])->name('combos.toggle');
        });
    });
});
