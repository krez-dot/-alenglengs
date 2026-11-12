<?php

use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\ProductController as ApiProductController;
use App\Http\Controllers\Api\SaleController as ApiSaleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('auth/token', [AuthTokenController::class, 'store'])->middleware('throttle:6,1');

Route::middleware(['auth:sanctum', 'role:admin,cashier'])->group(function () {
    Route::get('user', fn (Request $request) => $request->user());
    Route::delete('auth/token', [AuthTokenController::class, 'destroy']);

    Route::get('products', [ApiProductController::class, 'index'])->middleware('abilities:products:read');
    Route::get('products/{product}', [ApiProductController::class, 'show'])->middleware('abilities:products:read');
    Route::middleware(['role:admin', 'abilities:products:write'])->group(function () {
        Route::post('products', [ApiProductController::class, 'store']);
        Route::match(['put', 'patch'], 'products/{product}', [ApiProductController::class, 'update']);
        Route::delete('products/{product}', [ApiProductController::class, 'destroy']);
    });

    Route::get('sales', [ApiSaleController::class, 'index'])->middleware('abilities:sales:read');
    Route::get('sales/{sale}', [ApiSaleController::class, 'show'])->middleware('abilities:sales:read');
    Route::post('sales', [ApiSaleController::class, 'store'])->middleware('abilities:sales:create');
});
