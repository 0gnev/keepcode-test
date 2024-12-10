<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\UserProductController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [RegistrationController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

    Route::post('/products/{product}/purchase', [UserProductController::class, 'purchase'])->name('products.purchase');
    Route::post('/products/{product}/rent', [UserProductController::class, 'rent'])->name('products.rent');

    Route::post('/user-products/{userProduct}/renew', [UserProductController::class, 'renew'])->name('user-products.renew');
    Route::get('/user-products/{userProduct}/status', [UserProductController::class, 'status'])->name('user-products.status');

    Route::get('/user/purchase-history', [UserProductController::class, 'purchaseHistory'])->name('user.purchaseHistory');
});
