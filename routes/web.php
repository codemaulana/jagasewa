<?php

use App\Http\Controllers\Public\DocumentUploadController;
use App\Http\Controllers\Public\ItemController;
use App\Http\Controllers\Vendor\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public Document Upload Route (KTP & Selfie)
Route::post('/booking/upload', [DocumentUploadController::class, 'upload'])->name('public.booking.upload');

// Public Booking Success Route (static segment before wildcards)
Route::get('/b/success/{ulid}', [ItemController::class, 'bookingSuccess'])->name('public.booking.success');

// Public Vendor Catalog Route
Route::get('/b/{vendor_slug}', [ItemController::class, 'catalog'])->name('public.vendor.catalog');

// Public Item Catalog Route
Route::get('/b/{vendor_slug}/{item_slug}', [ItemController::class, 'show'])->name('public.items.show');

Route::prefix('vendor')->name('vendor.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout.submit');
});

// Route alias for Filament vendor panel logout action
Route::post('/vendor/filament-logout', [AuthController::class, 'logout'])->name('filament.vendor.auth.logout');
