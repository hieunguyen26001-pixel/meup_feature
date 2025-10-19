<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Partner\OrderApiController;
use App\Http\Controllers\Api\Partner\ReturnApiController;
use App\Http\Controllers\Api\Partner\TikTokAuthController;
use App\Http\Controllers\Api\Partner\ShopApiController;
use App\Http\Controllers\Api\Partner\ShopAnalyticsController;
use App\Http\Controllers\Api\Partner\ShopSkusAnalyticsController;
use App\Http\Controllers\Api\Partner\ShopVideosAnalyticsController;
use App\Http\Controllers\Api\Partner\ShopLivesAnalyticsController;
use App\Http\Controllers\Api\Partner\ProductApiController;
use App\Http\Controllers\Api\Partner\CancellationApiController;

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
Route::get('/cancellations/details', [CancellationApiController::class, 'show']);
Route::get('/cancellations/stats', [CancellationApiController::class, 'stats']);
Route::get('/products', [ProductApiController::class, 'index']);
Route::get('/orders', [OrderApiController::class, 'index']);
Route::get('/orders/details', [OrderApiController::class, 'show']);
Route::get('/orders/stats', [OrderApiController::class, 'stats']);
Route::get('/returns', [ReturnApiController::class, 'index']);
Route::get('/returns/details', [ReturnApiController::class, 'show']);
Route::get('/returns/stats', [ReturnApiController::class, 'stats']);

// TikTok OAuth routes
Route::get('/tiktok/authorize', [TikTokAuthController::class, 'authorize']);
Route::get('/oauth/shop/callback', [TikTokAuthController::class, 'callback']);

// Shop Analytics routes
Route::prefix('analytics')->group(function () {
    Route::get('/shops', [ShopAnalyticsController::class, 'shops']);
    Route::get('/shop/overview', [ShopAnalyticsController::class, 'shopOverview']);
    Route::get('/shop/performance', [ShopAnalyticsController::class, 'shopPerformance']);
    Route::get('/products', [ShopAnalyticsController::class, 'productAnalytics']);
    Route::get('/product/performance', [ShopAnalyticsController::class, 'productPerformance']);
    Route::get('/products/performance', [ShopAnalyticsController::class, 'productsPerformanceList']);
    Route::get('/videos', [ShopAnalyticsController::class, 'videoAnalytics']);
    Route::get('/live', [ShopAnalyticsController::class, 'liveAnalytics']);
    Route::get('/stats', [ShopAnalyticsController::class, 'stats']);
    
    // Shop SKUs Analytics routes
    Route::get('/shop/skus/performance', [ShopSkusAnalyticsController::class, 'getSkusPerformance']);
    Route::get('/shop/skus/summary', [ShopSkusAnalyticsController::class, 'getSkusSummary']);
    Route::get('/shop/skus/top', [ShopSkusAnalyticsController::class, 'getTopSkus']);
    
    // Shop Videos Analytics routes
    Route::get('/shop/videos/performance', [ShopVideosAnalyticsController::class, 'getVideosPerformance']);
    Route::get('/shop/videos/summary', [ShopVideosAnalyticsController::class, 'getVideosSummary']);
    Route::get('/shop/videos/top', [ShopVideosAnalyticsController::class, 'getTopVideos']);
    Route::get('/shop/videos/by-product', [ShopVideosAnalyticsController::class, 'getVideosByProduct']);
    Route::get('/shop/videos/overview', [ShopVideosAnalyticsController::class, 'getVideosOverviewPerformance']);
    Route::get('/shop/videos/{videoId}/performance', [ShopVideosAnalyticsController::class, 'getVideoPerformanceById']);
    
    // Shop Lives Analytics routes
    Route::get('/shop/lives/performance', [ShopLivesAnalyticsController::class, 'getLivesPerformance']);
    Route::get('/shop/lives/summary', [ShopLivesAnalyticsController::class, 'getLivesSummary']);
    Route::get('/shop/lives/top', [ShopLivesAnalyticsController::class, 'getTopLives']);
    Route::get('/shop/lives/overview', [ShopLivesAnalyticsController::class, 'getLivesOverviewPerformance']);
});