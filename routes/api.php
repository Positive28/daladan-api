<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Admin\AdminCheckController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SubCategoryController;
use App\Http\Controllers\Admin\UserController as AdminUserController;

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

    Route::prefix('public')->controller(PublicController::class)->group(function () {
        Route::get('/ads', 'ads');
        Route::get('/ads/{id}', 'ad');
    });

    Route::middleware('auth:api')->group(function () {
        
        Route::prefix('profile')->group(function () {
            Route::get('/', [UserController::class, 'profile']);
            Route::put('/', [UserController::class, 'updateProfile']);
            Route::post('/avatar', [UserController::class, 'updateAvatar']);
            Route::put('/password', [UserController::class, 'updatePassword']);

            Route::post('ads/{ad}', [AdsController::class, 'update']); // form-data + fayl uchun
            Route::get('ads/{ad}/stats', [AdsController::class, 'viewStats']);
            Route::apiResource('ads', AdsController::class);
        });

        Route::prefix('admin')->middleware('admin')->group(function () {
            Route::get('ads', [AdminCheckController::class, 'index']);
            Route::get('ads/{id}', [AdminCheckController::class, 'show']);
            Route::patch('ads/{id}/approve', [AdminCheckController::class, 'approve']);
            Route::patch('ads/{id}/reject', [AdminCheckController::class, 'reject']);
            Route::apiResource('categories', CategoryController::class);
            Route::apiResource('subcategories', SubCategoryController::class);
            Route::apiResource('users', AdminUserController::class);
        });

        Route::post('/logout', [AuthController::class, 'logout']);
    });

});


