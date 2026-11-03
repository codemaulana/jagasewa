<?php

use App\Http\Controllers\Api\ItemAvailabilityController;
use App\Http\Controllers\Webhooks\QrisWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

Route::get('/items/{ulid}/availability', ItemAvailabilityController::class)
    ->name('api.items.availability');

Route::post('/webhooks/qris', [QrisWebhookController::class, 'handle'])
    ->name('api.webhooks.qris');

Route::post('/qris/simulate/{referenceCode}', [QrisWebhookController::class, 'simulate'])
    ->name('api.qris.simulate');
