<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Seller\AccountController;
use App\Http\Controllers\Seller\AuthController as SellerAuthController;
use App\Http\Controllers\Seller\DashboardController;
use App\Http\Controllers\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Seller\StoreController;
use App\Http\Controllers\StorefrontProductController;
use Illuminate\Support\Facades\Route;

// 홈 인덱스
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest:buyer')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth:buyer')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/mypage', [ProfileController::class, 'show'])->name('mypage');
    Route::patch('/mypage', [ProfileController::class, 'update'])->name('mypage.update');
    Route::post('/mypage/addresses', [ProfileController::class, 'storeAddress'])->name('mypage.addresses.store');
    Route::patch('/mypage/addresses/{address}/default', [ProfileController::class, 'setDefaultAddress'])->name('mypage.addresses.default');
    Route::patch('/mypage/addresses/{address}', [ProfileController::class, 'updateAddress'])->name('mypage.addresses.update');
    Route::delete('/mypage/addresses/{address}', [ProfileController::class, 'destroyAddress'])->name('mypage.addresses.destroy');
    Route::post('/mypage/payment-methods', [ProfileController::class, 'storePaymentMethod'])->name('mypage.payments.store');
    Route::patch('/mypage/payment-methods/{paymentMethod}', [ProfileController::class, 'updatePaymentMethod'])->name('mypage.payments.update');
    Route::delete('/mypage/payment-methods/{paymentMethod}', [ProfileController::class, 'destroyPaymentMethod'])->name('mypage.payments.destroy');
    Route::post('/withdraw', [AuthController::class, 'withdraw'])->name('withdraw.store');
});

Route::get('/products/{product}', [StorefrontProductController::class, 'show'])->name('products.show');

Route::prefix('seller')->name('seller.')->group(function () {
    Route::middleware('guest:seller')->group(function () {
        Route::get('/login', [SellerAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [SellerAuthController::class, 'login'])->name('login.store');
    });

    Route::middleware('auth:seller')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('home');
        Route::post('/logout', [SellerAuthController::class, 'logout'])->name('logout');
        Route::get('/profile', [AccountController::class, 'edit'])->name('profile');
        Route::patch('/profile', [AccountController::class, 'update'])->name('profile.update');
        Route::patch('/password', [AccountController::class, 'password'])->name('password');
        Route::post('/withdraw', [AccountController::class, 'withdraw'])->name('withdraw');

        Route::get('/store', [StoreController::class, 'edit'])->name('store');
        Route::post('/store', [StoreController::class, 'update'])->name('store.update');

        Route::get('/products', [SellerProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [SellerProductController::class, 'create'])->name('products.create');
        Route::post('/products', [SellerProductController::class, 'store'])->name('products.store');
        Route::post('/products/bulk', [SellerProductController::class, 'bulk'])->name('products.bulk');
        Route::get('/products/{product}/edit', [SellerProductController::class, 'edit'])->name('products.edit');
        Route::post('/products/{product}', [SellerProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [SellerProductController::class, 'destroy'])->name('products.destroy');
        Route::get('/products/{product}/preview', [SellerProductController::class, 'preview'])->name('products.preview');
    });
});
