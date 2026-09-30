<?php

use App\Infrastructure\Http\Controllers\Api\{AuthController, CategoryController, ProductController, UserController};
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::middleware('jwt.auth')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/products', [ProductController::class, 'index']);

    Route::middleware('owner')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update'])->whereNumber('id');
        Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->whereNumber('id');

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update'])->whereNumber('id');
        Route::patch('/categories/{id}/toggle-status', [CategoryController::class, 'toggleStatus'])->whereNumber('id');
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->whereNumber('id');

        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update'])->whereNumber('id');
        Route::patch('/products/{id}/toggle-status', [ProductController::class, 'toggleStatus'])->whereNumber('id');
        Route::post('/products/{id}/stock', [ProductController::class, 'adjustStock'])->whereNumber('id');
        Route::get('/products/{id}/stock-history', [ProductController::class, 'stockHistory'])->whereNumber('id');
    });
});
