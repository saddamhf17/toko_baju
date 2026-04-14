<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('dashboard/summary', [DashboardController::class, 'summary']);

    Route::apiResource('products', ProductController::class);

    Route::post('pos/cart/items', [PosController::class, 'addItem']);
    Route::patch('pos/cart/items/{item}', [PosController::class, 'updateItem']);
    Route::delete('pos/cart/items/{item}', [PosController::class, 'removeItem']);
    Route::post('pos/checkout', [PosController::class, 'checkout']);
});
