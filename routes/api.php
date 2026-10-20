<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RESTful API Routes - Version 1 (v1)
|--------------------------------------------------------------------------
|
| Designed for production with token-based authentication (Laravel Sanctum),
| isolated validation requests, safe transactional mutations, search & filter,
| and file upload management.
|
*/

Route::prefix('v1')->group(function () {

    // ==========================================
    // Authentication Endpoints
    // ==========================================
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        });
    });

    // ==========================================
    // Category Management Endpoints
    // ==========================================
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::match(['put', 'patch'], '/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    });

    // ==========================================
    // Product Management Endpoints (Topi / Headwear)
    // ==========================================
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::match(['put', 'patch'], '/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

        // Dedicated File Upload Endpoint
        Route::post('/products/{product}/upload-image', [ProductController::class, 'uploadImage']);

        // Concurrency-Safe Stock Adjustments & Audit Trail
        Route::post('/products/{product}/adjust-stock', [ProductController::class, 'adjustStock']);
        Route::get('/products/{product}/stock-movements', [ProductController::class, 'stockMovements']);
    });
});
