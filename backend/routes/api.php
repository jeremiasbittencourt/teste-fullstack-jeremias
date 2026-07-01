<?php

use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Auth (público)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Rotas autenticadas
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Pedidos — /metrics precede /{id} para não colidir na rota.
    Route::get('/orders/metrics', [OrderController::class, 'metrics']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show'])->whereNumber('id');
    Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus']);

    // Afiliados
    Route::get('/affiliates/{id}/summary', [AffiliateController::class, 'summary'])->whereNumber('id');

    // Admin only — gestão de usuários (auth do dashboard)
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
    });
});
