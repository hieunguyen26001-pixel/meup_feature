<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Services\TikTokShopTokenService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ShopVideosAnalyticsController extends Controller
{
    use ApiResponseTrait;

    private TikTokShopTokenService $tokenService;

    public function __construct(TikTokShopTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Get shop videos performance analytics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getVideosPerformance(Request $request): JsonResponse
    {
        try {
            // Validate required parameters
            $request->validate([
                'start_date_ge' => 'required|date|date_format:Y-m-d',
                'end_date_lt' => 'required|date|date_format:Y-m-d|after:start_date_ge',
                'shop_cipher' => 'required|string',
                'app_key' => 'required|string',
            ]);

            // Get optional parameters with defaults
            $pageSize = $request->get('page_size', 10);
            $pageToken = $request->get('page_token');
            $sortField = $request->get('sort_field', 'gmv');
            $sortOrder = $request->get('sort_order', 'DESC');
            $currency = $request->get('currency', 'USD');
            $accountType = $request->get('account_type', 'ALL');
            $timestamp = $request->get('timestamp', time());

            // Get active shop
            $shop = $this->tokenService->getActiveShop();
            if (!$shop) {
                return $this->errorResponse('Không có shop nào được ủy quyền', 400);
            }

            // Get valid access token
            $token = $this->tokenService->getValidToken($shop->shop_id);
            if (!$token) {
                return $this->errorResponse('Không có token hợp lệ cho shop này', 401);
            }

            // Build TikTok Shop API URL
            $baseUrl = 'https://open-api.tiktokglobalshop.com';
            $endpoint = '/analytics/202409/shop_videos/performance';
            
            // Build query parameters
            $queryParams = [
                'start_date_ge' => $request->get('start_date_ge'),
                'end_date_lt' => $request->get('end_date_lt'),
                'sort_field' => $sortField,
                'sort_order' => $sortOrder,
                'currency' => $currency,
                'account_type' => $accountType,
                'app_key' => $request->get('app_key'),
                'shop_cipher' => $request->get('shop_cipher'),
                'page_size' => $pageSize,
                'timestamp' => $timestamp,
            ];

            // Add optional parameters
            if ($pageToken) {
                $queryParams['page_token'] = $pageToken;
            }

            // Generate signature (simplified - in production, implement proper HMAC)
            $signature = $this->generateSignature($queryParams, $shop->seller_cipher);
            $queryParams['sign'] = $signature;

            // Make API request to TikTok Shop
            $response = Http::withHeaders([
                'x-tts-access-token' => $token->access_token,
                'content-type' => 'application/json',
            ])->timeout(30)->get($baseUrl . $endpoint, $queryParams);

            if (!$response->successful()) {
                Log::error('TikTok Shop Videos Analytics API Error', [
                    'shop_id' => $shop->shop_id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'query_params' => $queryParams
                ]);

                return $this->errorResponse(
                    'Lỗi khi gọi TikTok Shop API: ' . $response->status() . '. Kiểm tra shop_cipher và app_key có đúng không',
                    500
                );
            }

            $data = $response->json();

            // Transform response data
            $transformedData = $this->transformVideosPerformanceData($data);

            return $this->successResponse([
                'videos_performance' => $transformedData['videos'] ?? [],
                'pagination' => [
                    'next_page_token' => $transformedData['next_page_token'] ?? null,
                    'total_count' => $transformedData['total_count'] ?? 0,
                    'page_size' => $pageSize,
                    'has_more' => !empty($transformedData['next_page_token'])
                ],
                'date_range' => [
                    'start_date' => $request->get('start_date_ge'),
                    'end_date' => $request->get('end_date_lt'),
                    'latest_available_date' => $transformedData['latest_available_date'] ?? null
                ],
                'filters' => [
                    'sort_field' => $sortField,
                    'sort_order' => $sortOrder,
                    'currency' => $currency,
                    'account_type' => $accountType
                ],
                'shop_id' => $shop->shop_id,
                'source' => 'tiktok_api'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Dữ liệu đầu vào không hợp lệ: ' . implode(', ', $e->errors()), 422);
        } catch (\Exception $e) {
            Log::error('Shop Videos Analytics Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return $this->errorResponse(
                'Lỗi hệ thống: ' . $e->getMessage() . '. Vui lòng thử lại sau hoặc liên hệ admin',
                500
            );
        }
    }

    /**
     * Get videos performance summary
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getVideosSummary(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start_date_ge' => 'required|date|date_format:Y-m-d',
                'end_date_lt' => 'required|date|date_format:Y-m-d|after:start_date_ge',
                'shop_cipher' => 'required|string',
                'app_key' => 'required|string',
            ]);

            // Get all videos data (with large page size)
            $request->merge(['page_size' => 100]);
            $performanceResponse = $this->getVideosPerformance($request);
            
            if (!$performanceResponse->getData()->success) {
                return $performanceResponse;
            }

            $data = $performanceResponse->getData()->data;
            $videos = $data->videos_performance ?? [];

            // Calculate summary statistics
            $summary = $this->calculateVideosSummary($videos);

            return $this->successResponse([
                'summary' => $summary,
                'date_range' => $data->date_range,
                'total_videos' => count($videos),
                'shop_id' => $data->shop_id ?? null,
                'source' => 'tiktok_api'
            ]);

        } catch (\Exception $e) {
            Log::error('Shop Videos Summary Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Lỗi khi tính toán summary: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get top performing videos
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTopVideos(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start_date_ge' => 'required|date|date_format:Y-m-d',
                'end_date_lt' => 'required|date|date_format:Y-m-d|after:start_date_ge',
                'shop_cipher' => 'required|string',
                'app_key' => 'required|string',
                'limit' => 'integer|min:1|max:50'
            ]);

            $limit = $request->get('limit', 10);
            
            // Get videos performance data
            $request->merge([
                'page_size' => $limit,
                'sort_field' => 'gmv',
                'sort_order' => 'DESC'
            ]);
            
            $performanceResponse = $this->getVideosPerformance($request);
            
            if (!$performanceResponse->getData()->success) {
                return $performanceResponse;
            }

            $data = $performanceResponse->getData()->data;
            $videos = $data->videos_performance ?? [];

            // Add ranking
            $rankedVideos = array_map(function($video, $index) {
                $video['rank'] = $index + 1;
                return $video;
            }, $videos, array_keys($videos));

            return $this->successResponse([
                'top_videos' => $rankedVideos,
                'date_range' => $data->date_range,
                'total_found' => count($videos),
                'shop_id' => $data->shop_id ?? null,
                'source' => 'tiktok_api'
            ]);

        } catch (\Exception $e) {
            Log::error('Top Videos Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Lỗi khi lấy top videos: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get videos overview performance
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getVideosOverviewPerformance(Request $request): JsonResponse
    {
        try {
            // Validate required parameters
            $request->validate([
                'start_date_ge' => 'required|date|date_format:Y-m-d',
                'end_date_lt' => 'required|date|date_format:Y-m-d|after:start_date_ge',
                'shop_cipher' => 'required|string',
                'app_key' => 'required|string',
            ]);

            // Get optional parameters with defaults
            $currency = $request->get('currency', 'USD');
            $accountType = $request->get('account_type', 'ALL');
            $withComparison = $request->get('with_comparison', true);
            $granularity = $request->get('granularity', 'ALL');
            $timestamp = $request->get('timestamp', time());

            // Get active shop
            $shop = $this->tokenService->getActiveShop();
            if (!$shop) {
                return $this->errorResponse('Không có shop nào được ủy quyền', 400);
            }

            // Get valid access token
            $token = $this->tokenService->getValidToken($shop->shop_id);
            if (!$token) {
                return $this->errorResponse('Không có token hợp lệ cho shop này', 401);
            }

            // Build TikTok Shop API URL
            $baseUrl = 'https://open-api.tiktokglobalshop.com';
            $endpoint = '/analytics/202409/shop_videos/overview_performance';
            
            // Build query parameters
            $queryParams = [
                'start_date_ge' => $request->get('start_date_ge'),
                'end_date_lt' => $request->get('end_date_lt'),
                'currency' => $currency,
                'account_type' => $accountType,
                'with_comparison' => $withComparison ? 'true' : 'false',
                'granularity' => $granularity,
                'app_key' => $request->get('app_key'),
                'shop_cipher' => $request->get('shop_cipher'),
                'timestamp' => $timestamp,
            ];

            // Generate signature (simplified - in production, implement proper HMAC)
            $signature = $this->generateSignature($queryParams, $shop->seller_cipher);
            $queryParams['sign'] = $signature;

            // Make API request to TikTok Shop
            $response = Http::withHeaders([
                'x-tts-access-token' => $token->access_token,
                'content-type' => 'application/json',
            ])->timeout(30)->get($baseUrl . $endpoint, $queryParams);

            if (!$response->successful()) {
                Log::error('TikTok Shop Videos Overview API Error', [
                    'shop_id' => $shop->shop_id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'query_params' => $queryParams
                ]);

                return $this->errorResponse(
                    'Lỗi khi gọi TikTok Shop API: ' . $response->status() . '. Kiểm tra shop_cipher và app_key có đúng không',
                    500
                );
            }

            $data = $response->json();

            // Transform response data
            $transformedData = $this->transformVideosOverviewData($data);

            return $this->successResponse([
                'overview_performance' => $transformedData['performance'] ?? [],
                'comparison_data' => $transformedData['comparison_intervals'] ?? [],
                'date_range' => [
                    'start_date' => $request->get('start_date_ge'),
                    'end_date' => $request->get('end_date_lt'),
                    'latest_available_date' => $transformedData['latest_available_date'] ?? null
                ],
                'filters' => [
                    'currency' => $currency,
                    'account_type' => $accountType,
                    'with_comparison' => $withComparison,
                    'granularity' => $granularity
                ],
                'shop_id' => $shop->shop_id,
                'source' => 'tiktok_api'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Dữ liệu đầu vào không hợp lệ: ' . implode(', ', $e->errors()), 422);
        } catch (\Exception $e) {
            Log::error('Shop Videos Overview Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return $this->errorResponse(
                'Lỗi hệ thống: ' . $e->getMessage() . '. Vui lòng thử lại sau hoặc liên hệ admin',
                500
            );
        }
    }

    /**
     * Get specific video performance by ID
     *
     * @param Request $request
     * @param string $videoId
     * @return JsonResponse
     */
    public function getVideoPerformanceById(Request $request, string $videoId): JsonResponse
    {
        try {
            // Validate required parameters
            $request->validate([
                'start_date_ge' => 'required|date|date_format:Y-m-d',
                'end_date_lt' => 'required|date|date_format:Y-m-d|after:start_date_ge',
                'shop_cipher' => 'required|string',
                'app_key' => 'required|string',
            ]);

            // Get optional parameters with defaults
            $currency = $request->get('currency', 'USD');
            $withComparison = $request->get('with_comparison', true);
            $granularity = $request->get('granularity', 'ALL');
            $timestamp = $request->get('timestamp', time());

            // Get active shop
            $shop = $this->tokenService->getActiveShop();
            if (!$shop) {
                return $this->errorResponse('Không có shop nào được ủy quyền', 400);
            }

            // Get valid access token
            $token = $this->tokenService->getValidToken($shop->shop_id);
            if (!$token) {
                return $this->errorResponse('Không có token hợp lệ cho shop này', 401);
            }

            // Build TikTok Shop API URL
            $baseUrl = 'https://open-api.tiktokglobalshop.com';
            $endpoint = "/analytics/202409/shop_videos/{$videoId}/performance";
            
            // Build query parameters
            $queryParams = [
                'start_date_ge' => $request->get('start_date_ge'),
                'end_date_lt' => $request->get('end_date_lt'),
                'currency' => $currency,
                'with_comparison' => $withComparison ? 'true' : 'false',
                'granularity' => $granularity,
                'app_key' => $request->get('app_key'),
                'shop_cipher' => $request->get('shop_cipher'),
                'timestamp' => $timestamp,
            ];

            // Generate signature (simplified - in production, implement proper HMAC)
            $signature = $this->generateSignature($queryParams, $shop->seller_cipher);
            $queryParams['sign'] = $signature;

            // Make API request to TikTok Shop
            $response = Http::withHeaders([
                'x-tts-access-token' => $token->access_token,
                'content-type' => 'application/json',
            ])->timeout(30)->get($baseUrl . $endpoint, $queryParams);

            if (!$response->successful()) {
                Log::error('TikTok Shop Video Performance API Error', [
                    'shop_id' => $shop->shop_id,
                    'video_id' => $videoId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'query_params' => $queryParams
                ]);

                return $this->errorResponse(
                    'Lỗi khi gọi TikTok Shop API: ' . $response->status() . '. Kiểm tra shop_cipher và app_key có đúng không',
                    500
                );
            }

            $data = $response->json();

            // Transform response data
            $transformedData = $this->transformVideoPerformanceByIdData($data, $videoId);

            return $this->successResponse([
                'video_id' => $videoId,
                'performance' => $transformedData['performance'] ?? [],
                'comparison_data' => $transformedData['comparison_intervals'] ?? [],
                'engagement_data' => $transformedData['engagement_data'] ?? [],
                'video_info' => $transformedData['video_info'] ?? [],
                'date_range' => [
                    'start_date' => $request->get('start_date_ge'),
                    'end_date' => $request->get('end_date_lt'),
                    'latest_available_date' => $transformedData['latest_available_date'] ?? null
                ],
                'filters' => [
                    'currency' => $currency,
                    'with_comparison' => $withComparison,
                    'granularity' => $granularity
                ],
                'shop_id' => $shop->shop_id,
                'source' => 'tiktok_api'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Dữ liệu đầu vào không hợp lệ: ' . implode(', ', $e->errors()), 422);
        } catch (\Exception $e) {
            Log::error('Video Performance By ID Error', [
                'video_id' => $videoId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return $this->errorResponse(
                'Lỗi hệ thống: ' . $e->getMessage() . '. Vui lòng thử lại sau hoặc liên hệ admin',
                500
            );
        }
    }

    /**
     * Get videos by product
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getVideosByProduct(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start_date_ge' => 'required|date|date_format:Y-m-d',
                'end_date_lt' => 'required|date|date_format:Y-m-d|after:start_date_ge',
                'shop_cipher' => 'required|string',
                'app_key' => 'required|string',
                'product_id' => 'required|string'
            ]);

            // Get all videos data
            $request->merge(['page_size' => 100]);
            $performanceResponse = $this->getVideosPerformance($request);
            
            if (!$performanceResponse->getData()->success) {
                return $performanceResponse;
            }

            $data = $performanceResponse->getData()->data;
            $allVideos = $data->videos_performance ?? [];

            // Filter videos by product ID
            $productId = $request->get('product_id');
            $filteredVideos = array_filter($allVideos, function($video) use ($productId) {
                if (!isset($video['products']) || !is_array($video['products'])) {
                    return false;
                }
                
                foreach ($video['products'] as $product) {
                    if (isset($product['id']) && $product['id'] === $productId) {
                        return true;
                    }
                }
                return false;
            });

            // Re-index array
            $filteredVideos = array_values($filteredVideos);

            return $this->successResponse([
                'videos' => $filteredVideos,
                'product_id' => $productId,
                'date_range' => $data->date_range,
                'total_found' => count($filteredVideos),
                'shop_id' => $data->shop_id ?? null,
                'source' => 'tiktok_api'
            ]);

        } catch (\Exception $e) {
            Log::error('Videos By Product Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Lỗi khi lấy videos theo product: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Transform TikTok Shop API overview response data
     *
     * @param array $data
     * @return array
     */
    private function transformVideosOverviewData(array $data): array
    {
        if (!isset($data['data']['performance'])) {
            return $data['data'] ?? [];
        }

        $performance = $data['data']['performance'];
        $transformedPerformance = [];
        $transformedComparison = [];

        // Transform main performance intervals
        if (isset($performance['intervals']) && is_array($performance['intervals'])) {
            foreach ($performance['intervals'] as $interval) {
                $transformedPerformance[] = [
                    'start_date' => $interval['start_date'] ?? null,
                    'end_date' => $interval['end_date'] ?? null,
                    'gmv' => [
                        'amount' => floatval($interval['gmv']['amount'] ?? 0),
                        'currency' => $interval['gmv']['currency'] ?? 'USD',
                        'formatted' => $this->formatCurrency(
                            floatval($interval['gmv']['amount'] ?? 0),
                            $interval['gmv']['currency'] ?? 'USD'
                        )
                    ],
                    'click_through_rate' => [
                        'rate' => floatval($interval['click_through_rate'] ?? 0),
                        'formatted' => number_format(floatval($interval['click_through_rate'] ?? 0), 2) . '%'
                    ],
                    'orders' => [
                        'count' => intval($interval['sku_orders'] ?? 0),
                        'formatted' => number_format($interval['sku_orders'] ?? 0)
                    ],
                    'units_sold' => [
                        'count' => intval($interval['units_sold'] ?? 0),
                        'formatted' => number_format($interval['units_sold'] ?? 0)
                    ],
                    'performance_metrics' => $this->calculateOverviewMetrics($interval)
                ];
            }
        }

        // Transform comparison intervals
        if (isset($performance['comparison_intervals']) && is_array($performance['comparison_intervals'])) {
            foreach ($performance['comparison_intervals'] as $interval) {
                $transformedComparison[] = [
                    'start_date' => $interval['start_date'] ?? null,
                    'end_date' => $interval['end_date'] ?? null,
                    'gmv' => [
                        'amount' => floatval($interval['gmv']['amount'] ?? 0),
                        'currency' => $interval['gmv']['currency'] ?? 'USD',
                        'formatted' => $this->formatCurrency(
                            floatval($interval['gmv']['amount'] ?? 0),
                            $interval['gmv']['currency'] ?? 'USD'
                        )
                    ],
                    'click_through_rate' => [
                        'rate' => floatval($interval['click_through_rate'] ?? 0),
                        'formatted' => number_format(floatval($interval['click_through_rate'] ?? 0), 2) . '%'
                    ],
                    'orders' => [
                        'count' => intval($interval['sku_orders'] ?? 0),
                        'formatted' => number_format($interval['sku_orders'] ?? 0)
                    ],
                    'units_sold' => [
                        'count' => intval($interval['units_sold'] ?? 0),
                        'formatted' => number_format($interval['units_sold'] ?? 0)
                    ],
                    'performance_metrics' => $this->calculateOverviewMetrics($interval)
                ];
            }
        }

        return [
            'performance' => $transformedPerformance,
            'comparison_intervals' => $transformedComparison,
            'latest_available_date' => $data['data']['latest_available_date'] ?? null
        ];
    }

    /**
     * Calculate overview performance metrics
     *
     * @param array $interval
     * @return array
     */
    private function calculateOverviewMetrics(array $interval): array
    {
        $gmv = floatval($interval['gmv']['amount'] ?? 0);
        $orders = intval($interval['sku_orders'] ?? 0);
        $units = intval($interval['units_sold'] ?? 0);
        $ctr = floatval($interval['click_through_rate'] ?? 0);
        $currency = $interval['gmv']['currency'] ?? 'USD';

        return [
            'average_order_value' => [
                'amount' => $orders > 0 ? $gmv / $orders : 0,
                'currency' => $currency,
                'formatted' => $this->formatCurrency($orders > 0 ? $gmv / $orders : 0, $currency)
            ],
            'units_per_order' => $orders > 0 ? round($units / $orders, 2) : 0,
            'gmv_per_unit' => [
                'amount' => $units > 0 ? $gmv / $units : 0,
                'currency' => $currency,
                'formatted' => $this->formatCurrency($units > 0 ? $gmv / $units : 0, $currency)
            ],
            'conversion_efficiency' => $this->calculateConversionEfficiency($interval)
        ];
    }

    /**
     * Calculate conversion efficiency score
     *
     * @param array $interval
     * @return float
     */
    private function calculateConversionEfficiency(array $interval): float
    {
        $gmv = floatval($interval['gmv']['amount'] ?? 0);
        $orders = intval($interval['sku_orders'] ?? 0);
        $units = intval($interval['units_sold'] ?? 0);
        $ctr = floatval($interval['click_through_rate'] ?? 0);

        // Simple efficiency score calculation
        $score = 0;
        
        if ($orders > 0) {
            $score += min(($gmv / 1000), 40); // GMV weight (capped at 40)
            $score += min(($orders / 10), 30); // Orders weight (capped at 30)
            $score += min(($units / 10), 20); // Units weight (capped at 20)
            $score += min(($ctr * 10), 10); // CTR weight (capped at 10)
        }
        
        return round(min($score, 100), 2); // Cap at 100
    }

    /**
     * Transform TikTok Shop API video performance by ID response data
     *
     * @param array $data
     * @param string $videoId
     * @return array
     */
    private function transformVideoPerformanceByIdData(array $data, string $videoId): array
    {
        if (!isset($data['data']['performance'])) {
            return $data['data'] ?? [];
        }

        $performance = $data['data']['performance'];
        $transformedPerformance = [];
        $transformedComparison = [];

        // Transform main performance intervals
        if (isset($performance['intervals']) && is_array($performance['intervals'])) {
            foreach ($performance['intervals'] as $interval) {
                $transformedPerformance[] = [
                    'start_date' => $interval['start_date'] ?? null,
                    'end_date' => $interval['end_date'] ?? null,
                    'gmv' => [
                        'amount' => floatval($interval['gmv']['amount'] ?? 0),
                        'currency' => $interval['gmv']['currency'] ?? 'USD',
                        'formatted' => $this->formatCurrency(
                            floatval($interval['gmv']['amount'] ?? 0),
                            $interval['gmv']['currency'] ?? 'USD'
                        )
                    ],
                    'click_through_rate' => [
                        'rate' => floatval($interval['click_through_rate'] ?? 0),
                        'formatted' => number_format(floatval($interval['click_through_rate'] ?? 0), 2) . '%'
                    ],
                    'daily_avg_buyers' => [
                        'count' => floatval($interval['daily_avg_buyers'] ?? 0),
                        'formatted' => number_format(floatval($interval['daily_avg_buyers'] ?? 0), 2)
                    ],
                    'views' => [
                        'count' => intval($interval['views'] ?? 0),
                        'formatted' => number_format($interval['views'] ?? 0)
                    ],
                    'performance_metrics' => $this->calculateVideoPerformanceMetrics($interval)
                ];
            }
        }

        // Transform comparison intervals
        if (isset($performance['comparison_intervals']) && is_array($performance['comparison_intervals'])) {
            foreach ($performance['comparison_intervals'] as $interval) {
                $transformedComparison[] = [
                    'start_date' => $interval['start_date'] ?? null,
                    'end_date' => $interval['end_date'] ?? null,
                    'gmv' => [
                        'amount' => floatval($interval['gmv']['amount'] ?? 0),
                        'currency' => $interval['gmv']['currency'] ?? 'USD',
                        'formatted' => $this->formatCurrency(
                            floatval($interval['gmv']['amount'] ?? 0),
                            $interval['gmv']['currency'] ?? 'USD'
                        )
                    ],
                    'click_through_rate' => [
                        'rate' => floatval($interval['click_through_rate'] ?? 0),
                        'formatted' => number_format(floatval($interval['click_through_rate'] ?? 0), 2) . '%'
                    ],
                    'daily_avg_buyers' => [
                        'count' => floatval($interval['daily_avg_buyers'] ?? 0),
                        'formatted' => number_format(floatval($interval['daily_avg_buyers'] ?? 0), 2)
                    ],
                    'views' => [
                        'count' => intval($interval['views'] ?? 0),
                        'formatted' => number_format($interval['views'] ?? 0)
                    ],
                    'performance_metrics' => $this->calculateVideoPerformanceMetrics($interval)
                ];
            }
        }

        // Transform engagement data
        $engagementData = [];
        if (isset($data['data']['engagement_data'])) {
            $engagement = $data['data']['engagement_data'];
            $engagementData = [
                'total_likes' => [
                    'count' => intval($engagement['total_likes'] ?? 0),
                    'formatted' => number_format($engagement['total_likes'] ?? 0)
                ],
                'total_shares' => [
                    'count' => intval($engagement['total_shares'] ?? 0),
                    'formatted' => number_format($engagement['total_shares'] ?? 0)
                ],
                'total_comments' => [
                    'count' => intval($engagement['total_comments'] ?? 0),
                    'formatted' => number_format($engagement['total_comments'] ?? 0)
                ],
                'total_views' => [
                    'count' => intval($engagement['total_views'] ?? 0),
                    'formatted' => number_format($engagement['total_views'] ?? 0)
                ],
                'engagement_metrics' => $this->calculateEngagementMetrics($engagement)
            ];
        }

        // Transform video info
        $videoInfo = [];
        if (isset($performance['video_post_time'])) {
            $videoInfo = [
                'video_post_time' => $performance['video_post_time'],
                'video_post_date' => $this->formatVideoPostDate($performance['video_post_time'])
            ];
        }

        return [
            'performance' => $transformedPerformance,
            'comparison_intervals' => $transformedComparison,
            'engagement_data' => $engagementData,
            'video_info' => $videoInfo,
            'latest_available_date' => $data['data']['latest_available_date'] ?? null
        ];
    }

    /**
     * Calculate video performance metrics
     *
     * @param array $interval
     * @return array
     */
    private function calculateVideoPerformanceMetrics(array $interval): array
    {
        $gmv = floatval($interval['gmv']['amount'] ?? 0);
        $views = intval($interval['views'] ?? 0);
        $dailyAvgBuyers = floatval($interval['daily_avg_buyers'] ?? 0);
        $ctr = floatval($interval['click_through_rate'] ?? 0);
        $currency = $interval['gmv']['currency'] ?? 'USD';

        return [
            'gmv_per_view' => [
                'amount' => $views > 0 ? $gmv / $views : 0,
                'currency' => $currency,
                'formatted' => $this->formatCurrency($views > 0 ? $gmv / $views : 0, $currency)
            ],
            'buyer_conversion_rate' => $views > 0 ? round(($dailyAvgBuyers / $views) * 100, 2) : 0,
            'engagement_rate' => $views > 0 ? round($ctr, 2) : 0,
            'performance_score' => $this->calculateVideoPerformanceScore($interval)
        ];
    }

    /**
     * Calculate engagement metrics
     *
     * @param array $engagement
     * @return array
     */
    private function calculateEngagementMetrics(array $engagement): array
    {
        $likes = intval($engagement['total_likes'] ?? 0);
        $shares = intval($engagement['total_shares'] ?? 0);
        $comments = intval($engagement['total_comments'] ?? 0);
        $views = intval($engagement['total_views'] ?? 0);

        return [
            'like_rate' => $views > 0 ? round(($likes / $views) * 100, 2) : 0,
            'share_rate' => $views > 0 ? round(($shares / $views) * 100, 2) : 0,
            'comment_rate' => $views > 0 ? round(($comments / $views) * 100, 2) : 0,
            'total_engagement' => $likes + $shares + $comments,
            'engagement_score' => $this->calculateEngagementScore($engagement)
        ];
    }

    /**
     * Calculate video performance score
     *
     * @param array $interval
     * @return float
     */
    private function calculateVideoPerformanceScore(array $interval): float
    {
        $gmv = floatval($interval['gmv']['amount'] ?? 0);
        $views = intval($interval['views'] ?? 0);
        $dailyAvgBuyers = floatval($interval['daily_avg_buyers'] ?? 0);
        $ctr = floatval($interval['click_through_rate'] ?? 0);

        $score = 0;
        
        if ($views > 0) {
            $score += min(($gmv / 1000), 30); // GMV weight (capped at 30)
            $score += min(($views / 10000), 25); // Views weight (capped at 25)
            $score += min(($dailyAvgBuyers / 100), 25); // Buyers weight (capped at 25)
            $score += min(($ctr * 10), 20); // CTR weight (capped at 20)
        }
        
        return round(min($score, 100), 2); // Cap at 100
    }


    /**
     * Transform TikTok Shop API response data
     *
     * @param array $data
     * @return array
     */
    private function transformVideosPerformanceData(array $data): array
    {
        if (!isset($data['data']['videos'])) {
            return $data['data'] ?? [];
        }

        $videos = $data['data']['videos'];
        $transformedVideos = [];

        foreach ($videos as $video) {
            $transformedVideos[] = [
                'video_id' => $video['id'] ?? null,
                'title' => $video['title'] ?? '',
                'username' => $video['username'] ?? '',
                'gmv' => [
                    'amount' => floatval($video['gmv']['amount'] ?? 0),
                    'currency' => $video['gmv']['currency'] ?? 'USD',
                    'formatted' => $this->formatCurrency(
                        floatval($video['gmv']['amount'] ?? 0),
                        $video['gmv']['currency'] ?? 'USD'
                    )
                ],
                'orders' => [
                    'count' => intval($video['sku_orders'] ?? 0),
                    'formatted' => number_format($video['sku_orders'] ?? 0)
                ],
                'units_sold' => [
                    'count' => intval($video['units_sold'] ?? 0),
                    'formatted' => number_format($video['units_sold'] ?? 0)
                ],
                'views' => [
                    'count' => intval($video['views'] ?? 0),
                    'formatted' => number_format($video['views'] ?? 0)
                ],
                'click_through_rate' => [
                    'rate' => floatval($video['click_through_rate'] ?? 0),
                    'formatted' => number_format(floatval($video['click_through_rate'] ?? 0), 2) . '%'
                ],
                'products' => $this->transformProducts($video['products'] ?? []),
                'video_post_time' => $video['video_post_time'] ?? null,
                'video_post_date' => $this->formatVideoPostDate($video['video_post_time'] ?? null),
                'performance_metrics' => $this->calculateVideoMetrics($video)
            ];
        }

        return [
            'videos' => $transformedVideos,
            'next_page_token' => $data['data']['next_page_token'] ?? null,
            'total_count' => $data['data']['total_count'] ?? 0,
            'latest_available_date' => $data['data']['latest_available_date'] ?? null
        ];
    }

    /**
     * Transform products data
     *
     * @param array $products
     * @return array
     */
    private function transformProducts(array $products): array
    {
        $transformedProducts = [];
        
        foreach ($products as $product) {
            $transformedProducts[] = [
                'product_id' => $product['id'] ?? null,
                'name' => $product['name'] ?? '',
                'display_name' => $this->truncateText($product['name'] ?? '', 50)
            ];
        }
        
        return $transformedProducts;
    }

    /**
     * Calculate video performance metrics
     *
     * @param array $video
     * @return array
     */
    private function calculateVideoMetrics(array $video): array
    {
        $gmv = floatval($video['gmv']['amount'] ?? 0);
        $orders = intval($video['sku_orders'] ?? 0);
        $units = intval($video['units_sold'] ?? 0);
        $views = intval($video['views'] ?? 0);
        $ctr = floatval($video['click_through_rate'] ?? 0);
        $currency = $video['gmv']['currency'] ?? 'USD';

        return [
            'average_order_value' => [
                'amount' => $orders > 0 ? $gmv / $orders : 0,
                'currency' => $currency,
                'formatted' => $this->formatCurrency($orders > 0 ? $gmv / $orders : 0, $currency)
            ],
            'conversion_rate' => $views > 0 ? round(($orders / $views) * 100, 2) : 0,
            'units_per_order' => $orders > 0 ? round($units / $orders, 2) : 0,
            'revenue_per_view' => [
                'amount' => $views > 0 ? $gmv / $views : 0,
                'currency' => $currency,
                'formatted' => $this->formatCurrency($views > 0 ? $gmv / $views : 0, $currency)
            ],
            'engagement_score' => $this->calculateEngagementScore($video)
        ];
    }

    /**
     * Calculate engagement score for video
     *
     * @param array $video
     * @return float
     */
    private function calculateEngagementScore(array $video): float
    {
        $gmv = floatval($video['gmv']['amount'] ?? 0);
        $orders = intval($video['sku_orders'] ?? 0);
        $units = intval($video['units_sold'] ?? 0);
        $views = intval($video['views'] ?? 0);
        $ctr = floatval($video['click_through_rate'] ?? 0);

        // Simple engagement score calculation
        $score = 0;
        
        if ($views > 0) {
            $score += ($orders / $views) * 40; // Conversion rate weight
            $score += ($ctr / 100) * 30; // CTR weight
            $score += min(($gmv / 1000), 30); // GMV weight (capped at 30)
        }
        
        return round(min($score, 100), 2); // Cap at 100
    }

    /**
     * Calculate summary statistics for videos
     *
     * @param array $videos
     * @return array
     */
    private function calculateVideosSummary(array $videos): array
    {
        if (empty($videos)) {
            return [
                'total_gmv' => ['amount' => 0, 'currency' => 'USD', 'formatted' => '$0.00'],
                'total_orders' => 0,
                'total_units_sold' => 0,
                'total_views' => 0,
                'average_gmv_per_video' => ['amount' => 0, 'currency' => 'USD', 'formatted' => '$0.00'],
                'average_orders_per_video' => 0,
                'average_views_per_video' => 0,
                'average_ctr' => 0,
                'top_video' => null,
                'total_videos' => 0
            ];
        }

        $totalGmv = 0;
        $totalOrders = 0;
        $totalUnits = 0;
        $totalViews = 0;
        $totalCtr = 0;
        $topVideo = null;
        $maxGmv = 0;

        foreach ($videos as $video) {
            $gmv = $video['gmv']['amount'] ?? 0;
            $orders = $video['orders']['count'] ?? 0;
            $units = $video['units_sold']['count'] ?? 0;
            $views = $video['views']['count'] ?? 0;
            $ctr = $video['click_through_rate']['rate'] ?? 0;

            $totalGmv += $gmv;
            $totalOrders += $orders;
            $totalUnits += $units;
            $totalViews += $views;
            $totalCtr += $ctr;

            if ($gmv > $maxGmv) {
                $maxGmv = $gmv;
                $topVideo = $video;
            }
        }

        $videoCount = count($videos);
        $currency = $videos[0]['gmv']['currency'] ?? 'USD';

        return [
            'total_gmv' => [
                'amount' => $totalGmv,
                'currency' => $currency,
                'formatted' => $this->formatCurrency($totalGmv, $currency)
            ],
            'total_orders' => $totalOrders,
            'total_units_sold' => $totalUnits,
            'total_views' => $totalViews,
            'average_gmv_per_video' => [
                'amount' => $videoCount > 0 ? $totalGmv / $videoCount : 0,
                'currency' => $currency,
                'formatted' => $this->formatCurrency($videoCount > 0 ? $totalGmv / $videoCount : 0, $currency)
            ],
            'average_orders_per_video' => $videoCount > 0 ? round($totalOrders / $videoCount, 2) : 0,
            'average_views_per_video' => $videoCount > 0 ? round($totalViews / $videoCount, 2) : 0,
            'average_ctr' => $videoCount > 0 ? round($totalCtr / $videoCount, 2) : 0,
            'top_video' => $topVideo,
            'total_videos' => $videoCount
        ];
    }

    /**
     * Format video post date
     *
     * @param string|null $postTime
     * @return array|null
     */
    private function formatVideoPostDate(?string $postTime): ?array
    {
        if (!$postTime) {
            return null;
        }

        try {
            $date = Carbon::parse($postTime);
            return [
                'raw' => $postTime,
                'formatted' => $date->format('M d, Y H:i'),
                'relative' => $date->diffForHumans(),
                'timestamp' => $date->timestamp
            ];
        } catch (\Exception $e) {
            return [
                'raw' => $postTime,
                'formatted' => $postTime,
                'relative' => 'Unknown',
                'timestamp' => null
            ];
        }
    }

    /**
     * Truncate text to specified length
     *
     * @param string $text
     * @param int $length
     * @return string
     */
    private function truncateText(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . '...';
    }

    /**
     * Format currency amount
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    private function formatCurrency(float $amount, string $currency = 'USD'): string
    {
        $symbols = [
            'USD' => '$',
            'VND' => '₫',
            'EUR' => '€',
            'GBP' => '£'
        ];

        $symbol = $symbols[$currency] ?? $currency . ' ';
        return $symbol . number_format($amount, 2);
    }

    /**
     * Generate signature for TikTok Shop API
     * Note: This is a simplified version. In production, implement proper HMAC-SHA256
     *
     * @param array $params
     * @param string $secret
     * @return string
     */
    private function generateSignature(array $params, string $secret): string
    {
        // Sort parameters
        ksort($params);
        
        // Create query string
        $queryString = http_build_query($params);
        
        // In production, implement proper HMAC-SHA256 signature
        // For now, return a mock signature
        return hash('sha256', $queryString . $secret);
    }
}
