<?php

use App\Http\Controllers\Api\EcommerceOrderInboundController;
use App\Http\Controllers\Api\Storefront\StorefrontCategoryController;
use App\Http\Controllers\Api\Storefront\StorefrontProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/ecommerce')
    ->middleware(['tenant.key', 'throttle:120,1'])
    ->group(function () {
        Route::get('/ping', function () {
            $t = tenant();

            return response()->json([
                'ok' => true,
                'tenant' => [
                    'id' => $t->id,
                    'name' => $t->name ?? null,
                    'subdomain' => $t->subdomain ?? null,
                ],
                'server_time' => now()->toIso8601String(),
            ]);
        })->name('api.ecommerce.ping');

        Route::post('/orders', [EcommerceOrderInboundController::class, 'upsert'])
            ->name('api.ecommerce.orders.upsert');
    });

Route::prefix('store/{tenant}')
    ->middleware(['throttle:120,1'])
    ->group(function () {
        Route::get('/categories', [StorefrontCategoryController::class, 'index']);
        Route::get('/categories/{slug}/products', [StorefrontCategoryController::class, 'products']);

        Route::get('/products', [StorefrontProductController::class, 'index']);
        Route::get('/products/{slug}', [StorefrontProductController::class, 'show']);
    });