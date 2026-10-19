<?php

use App\Http\Controllers\Public\DocumentUploadController;
use App\Http\Controllers\Public\ItemController;
use App\Http\Controllers\Vendor\AuthController;
use App\Http\Controllers\Vendor\BlacklistCheckController;
use App\Http\Controllers\Vendor\IdentityDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public Document Upload Route (KTP & Selfie) - Throttled 5/15min (SEC-11)
Route::post('/booking/upload', [DocumentUploadController::class, 'upload'])
    ->middleware('throttle:booking.upload')
    ->name('public.booking.upload');

// Public Booking Success Route (static segment before wildcards)
Route::get('/b/success/{ulid}', [ItemController::class, 'bookingSuccess'])->name('public.booking.success');

// Public Vendor Catalog Route
Route::get('/b/{vendor_slug}', [ItemController::class, 'catalog'])->name('public.vendor.catalog');

// Public Item Catalog Route
Route::get('/b/{vendor_slug}/{item_slug}', [ItemController::class, 'show'])->name('public.items.show');

Route::prefix('vendor')->name('vendor.')->group(function () {
    // Vendor Auth - Login Throttled 5/min (SEC-11)
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.submit');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout.submit');

    // Identity Documents (IDOR-03 / SEC-27) - Throttled 20/min (SEC-11)
    Route::match(['GET', 'POST'], '/identity-documents/{ulid}/signed-url', [IdentityDocumentController::class, 'signedUrl'])
        ->middleware(['auth:vendor', 'throttle:signed-url'])
        ->name('identity-documents.signed-url');

    // Internal Blacklist Check (SEC-11) - Throttled 30/min
    Route::get('/blacklist/check', [BlacklistCheckController::class, 'check'])
        ->middleware(['auth:vendor', 'throttle:blacklist.check'])
        ->name('blacklist.check');

    // Secure view / streaming with signed URL verification (TC-40)
    Route::get('/identity-documents/{ulid}/secure-view', [IdentityDocumentController::class, 'secureView'])
        ->middleware('signed')
        ->name('identity-documents.secure-view');
});

// Route alias for Filament vendor panel logout action
Route::post('/vendor/filament-logout', [AuthController::class, 'logout'])->name('filament.vendor.auth.logout');
