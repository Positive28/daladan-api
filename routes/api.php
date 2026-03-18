<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\Admin\CategoryController;
use Illuminate\Http\Request;

Route::group(['prefix' => 'v1', 'middleware' => 'api'], function ($router) {

    // Tekshirish: GET /api/v1 — serverda API ishlayotganini tekshirish
    Route::get('/', fn () => response()->json(['api' => 'v1', 'status' => 'ok', 'message' => 'API ishlayapti']));

    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Viloyat va tumanlar ro'yxati (public, registratsiya formasi uchun)
    Route::prefix('resources')->controller(ResourceController::class)->group(function () {
        Route::get('/regions', 'regions');
        Route::get('/cities', 'cities');
        Route::get('/categories', 'categories');
        Route::get('/subcategories', 'subcategories');
    });

    Route::middleware('auth:api')->group(function () {
        
        Route::prefix('profile')->group(function () {
            Route::get('/', [UserController::class, 'profile']);
            Route::put('/', [UserController::class, 'updateProfile']);
            Route::post('/avatar', [UserController::class, 'updateAvatar']);
            Route::put('/password', [UserController::class, 'updatePassword']);
            // Profil sahifasidan e'lon qo'shish
            Route::post('/ads', [AdController::class, 'store']);
        });
        Route::apiResource('/ads', AdController::class);

        Route::prefix('admin')->group(function () {
            Route::apiResource('categories', CategoryController::class);
        });

        Route::post('/logout', [AuthController::class, 'logout']);
    });

});


