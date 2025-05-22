<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\OrderController;




Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::group(['prefix' => 'v1'], function() {
    // Get one brand
    Route::get('/brands/{id}', [BrandController::class, 'show']);

    // Get all brands
    Route::get('/brands', [BrandController::class, 'brands']);
    
    // Create new brand
    Route::post('/brands', [BrandController::class, 'storeApi']);

        // Get brand for editing
    Route::get('/brands/{id}/edit', [BrandController::class, 'edit']);
    
        // Update brand
    Route::put('/brands/{id}', [BrandController::class, 'update']);

      // Delete brand
    Route::delete('/brands/{id}', [BrandController::class, 'destroy']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::get('/products/create', [ProductController::class, 'create']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{id}/edit', [ProductController::class, 'edit']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    Route::get('/coupons', [CouponController::class, 'index']);
    Route::get('/coupons/{id}', [CouponController::class, 'show']);
    Route::post('/coupons/validate', [CouponController::class, 'validateCoupon']);
    Route::post('/coupons', [CouponController::class, 'store']);
    Route::put('/coupons/{id}', [CouponController::class, 'update']);
    Route::delete('/coupons/{id}', [CouponController::class, 'destroy']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order_id}/items', [OrderController::class, 'orderItems']);
    Route::put('/orders/{order_id}/status', [OrderController::class, 'updateStatus']);


});