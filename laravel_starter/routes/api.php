<?php

use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', ProductController::class);

    Route::post('pos/cart/items', [PosController::class, 'addItem']);
    Route::patch('pos/cart/items/{item}', [PosController::class, 'updateItem']);
    Route::delete('pos/cart/items/{item}', [PosController::class, 'removeItem']);
    Route::post('pos/checkout', [PosController::class, 'checkout']);
});
