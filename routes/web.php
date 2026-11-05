<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
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
