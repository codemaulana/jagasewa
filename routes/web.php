<?php

use App\Http\Controllers\Public\ItemController;
use App\Http\Controllers\Vendor\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public Vendor Catalog Route
Route::get('/b/{vendor_slug}', [ItemController::class, 'catalog'])->name('public.vendor.catalog');

// Public Item Catalog Route
Route::get('/b/{vendor_slug}/{item_slug}', [ItemController::class, 'show'])->name('public.items.show');

// Public Booking Success Route
Route::get('/b/success/{ulid}', [ItemController::class, 'bookingSuccess'])->name('public.booking.success');

Route::prefix('vendor')->name('vendor.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout.submit');
});

// Route alias for Filament vendor panel logout action
Route::post('/vendor/filament-logout', [AuthController::class, 'logout'])->name('filament.vendor.auth.logout');
