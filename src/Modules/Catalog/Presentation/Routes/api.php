<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Yeod\Modules\Catalog\Presentation\Http\Controllers\ProductController;

/**
 * Catalog API routes.
 *
 * Loaded by `CatalogServiceProvider` with the `api` middleware group and the `api`
 * prefix, which yields `/api/catalog/products`.
 */
Route::prefix('catalog')->name('catalog.')->group(function (): void {
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::get('products/{productId}', [ProductController::class, 'show'])->name('products.show');
});
