<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CancellationApiController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\TikTokAuthController;
use App\Http\Controllers\Api\ShopApiController;
use App\Http\Controllers\Api\ShopAnalyticsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API Routes for Vue.js frontend
Route::get('/shops', [ShopApiController::class, 'index']);
Route::get('/cancellations', [CancellationApiController::class, 'index']);
Route::get('/products', [ProductApiController::class, 'index']);
Route::get('/orders', [OrderApiController::class, 'index']);
Route::get('/orders/details', [OrderApiController::class, 'show']);
Route::get('/orders/stats', [OrderApiController::class, 'stats']);

// TikTok OAuth routes
Route::get('/tiktok/authorize', [TikTokAuthController::class, 'authorize']);
Route::get('/oauth/shop/callback', [TikTokAuthController::class, 'callback']);

// Shop Analytics routes
Route::prefix('analytics')->group(function () {
    Route::get('/shops', [ShopAnalyticsController::class, 'shops']);
    Route::get('/shop/overview', [ShopAnalyticsController::class, 'shopOverview']);
    Route::get('/products', [ShopAnalyticsController::class, 'productAnalytics']);
    Route::get('/videos', [ShopAnalyticsController::class, 'videoAnalytics']);
    Route::get('/live', [ShopAnalyticsController::class, 'liveAnalytics']);
    Route::get('/stats', [ShopAnalyticsController::class, 'stats']);
});