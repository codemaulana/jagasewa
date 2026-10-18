<?php

use App\Http\Controllers\Vendor\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('vendor')->name('vendor.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout.submit');
});

// Route alias for Filament vendor panel logout action
Route::post('/vendor/filament-logout', [AuthController::class, 'logout'])->name('filament.vendor.auth.logout');
