<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// 홈 인덱스
Route::get('/', [HomeController::class, 'index'])->name('home');
