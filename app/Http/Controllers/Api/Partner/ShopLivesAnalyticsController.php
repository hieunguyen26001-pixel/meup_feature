<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Services\TikTokShopTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopLivesAnalyticsController extends Controller
{
    private TikTokShopTokenService $tokenService;

    public function __construct(TikTokShopTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Get lives performance
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLivesPerformance(Request $request): JsonResponse
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
            $endpoint = '/analytics/202508/shop_lives/performance';
            
            // Build query parameters
            $queryParams = [
                'start_date_ge' => $request->get('start_date_ge'),
                'end_date_lt' => $request->get('end_date_lt'),
                'page_size' => $pageSize,
                'sort_field' => $sortField,
                'sort_order' => $sortOrder,
                'currency' => $currency,
                'account_type' => $accountType,
                'app_key' => $request->get('app_key'),
                'shop_cipher' => $request->get('shop_cipher'),
                'timestamp' => $timestamp,
            ];

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
                Log::error('TikTok Shop Lives Performance API Error', [
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
            $transformedData = $this->transformLivesPerformanceData($data);

            return $this->successResponse([
                'live_stream_sessions' => $transformedData['live_stream_sessions'] ?? [],
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
            Log::error('Shop Lives Performance Error', [
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
     * Get lives performance summary
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLivesSummary(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start_date_ge' => 'required|date|date_format:Y-m-d',
                'end_date_lt' => 'required|date|date_format:Y-m-d|after:start_date_ge',
                'shop_cipher' => 'required|string',
                'app_key' => 'required|string',
            ]);

            // Get lives performance data first
            $performanceResponse = $this->getLivesPerformance($request);
            $data = $performanceResponse->getData(true);

            if (!$data['success']) {
                return $performanceResponse;
            }

            $liveStreams = $data['data']['live_stream_sessions'] ?? [];

            // Calculate summary statistics
            $summary = $this->calculateLivesSummary($liveStreams);

            return $this->successResponse([
                'summary' => $summary,
                'date_range' => $data['data']['date_range'],
                'total_live_streams' => count($liveStreams),
                'shop_id' => $data['data']['shop_id'],
                'source' => 'tiktok_api'
            ]);

        } catch (\Exception $e) {
            Log::error('Shop Lives Summary Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Lỗi khi lấy thống kê lives: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get lives overview performance
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLivesOverviewPerformance(Request $request): JsonResponse
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
            $granularity = $request->get('granularity', '1D');
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
            $endpoint = '/analytics/202508/shop_lives/overview_performance';
            
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
                Log::error('TikTok Shop Lives Overview API Error', [
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
            $transformedData = $this->transformLivesOverviewData($data);

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
            Log::error('Shop Lives Overview Error', [
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
     * Get top performing lives
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTopLives(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start_date_ge' => 'required|date|date_format:Y-m-d',
                'end_date_lt' => 'required|date|date_format:Y-m-d|after:start_date_ge',
                'shop_cipher' => 'required|string',
                'app_key' => 'required|string',
            ]);

            $limit = min($request->get('limit', 10), 50); // Max 50

            // Get lives performance data first
            $performanceResponse = $this->getLivesPerformance($request);
            $data = $performanceResponse->getData(true);

            if (!$data['success']) {
                return $performanceResponse;
            }

            $liveStreams = $data['data']['live_stream_sessions'] ?? [];

            // Sort by GMV and get top performing
            usort($liveStreams, function($a, $b) {
                return $b['sales_performance']['gmv']['amount'] <=> $a['sales_performance']['gmv']['amount'];
            });

            $topLives = array_slice($liveStreams, 0, $limit);

            // Add ranking
            $rankedLives = array_map(function($live, $index) {
                $live['rank'] = $index + 1;
                return $live;
            }, $topLives, array_keys($topLives));

            return $this->successResponse([
                'top_lives' => $rankedLives,
                'date_range' => $data['data']['date_range'],
                'total_found' => count($liveStreams),
                'shop_id' => $data['data']['shop_id'],
                'source' => 'tiktok_api'
            ]);

        } catch (\Exception $e) {
            Log::error('Top Lives Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Lỗi khi lấy top lives: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Transform TikTok Shop API lives overview response data
     *
     * @param array $data
     * @return array
     */
    private function transformLivesOverviewData(array $data): array
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
                    'sku_orders' => [
                        'count' => intval($interval['sku_orders'] ?? 0),
                        'formatted' => number_format($interval['sku_orders'] ?? 0)
                    ],
                    'customers' => [
                        'count' => intval($interval['customers'] ?? 0),
                        'formatted' => number_format($interval['customers'] ?? 0)
                    ],
                    'units_sold' => [
                        'count' => intval($interval['units_sold'] ?? 0),
                        'formatted' => number_format($interval['units_sold'] ?? 0)
                    ],
                    'click_to_order_rate' => [
                        'rate' => floatval(str_replace('%', '', $interval['click_to_order_rate'] ?? '0')),
                        'formatted' => $interval['click_to_order_rate'] ?? '0%'
                    ],
                    'click_through_rate' => [
                        'rate' => floatval(str_replace('%', '', $interval['click_through_rate'] ?? '0')),
                        'formatted' => $interval['click_through_rate'] ?? '0%'
                    ],
                    'performance_metrics' => $this->calculateLivesOverviewMetrics($interval)
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
                    'sku_orders' => [
                        'count' => intval($interval['sku_orders'] ?? 0),
                        'formatted' => number_format($interval['sku_orders'] ?? 0)
                    ],
                    'customers' => [
                        'count' => intval($interval['customers'] ?? 0),
                        'formatted' => number_format($interval['customers'] ?? 0)
                    ],
                    'units_sold' => [
                        'count' => intval($interval['units_sold'] ?? 0),
                        'formatted' => number_format($interval['units_sold'] ?? 0)
                    ],
                    'click_to_order_rate' => [
                        'rate' => floatval(str_replace('%', '', $interval['click_to_order_rate'] ?? '0')),
                        'formatted' => $interval['click_to_order_rate'] ?? '0%'
                    ],
                    'click_through_rate' => [
                        'rate' => floatval(str_replace('%', '', $interval['click_through_rate'] ?? '0')),
                        'formatted' => $interval['click_through_rate'] ?? '0%'
                    ],
                    'performance_metrics' => $this->calculateLivesOverviewMetrics($interval)
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
     * Calculate lives overview performance metrics
     *
     * @param array $interval
     * @return array
     */
    private function calculateLivesOverviewMetrics(array $interval): array
    {
        $gmv = floatval($interval['gmv']['amount'] ?? 0);
        $skuOrders = intval($interval['sku_orders'] ?? 0);
        $customers = intval($interval['customers'] ?? 0);
        $unitsSold = intval($interval['units_sold'] ?? 0);
        $ctoRate = floatval(str_replace('%', '', $interval['click_to_order_rate'] ?? '0'));
        $ctr = floatval(str_replace('%', '', $interval['click_through_rate'] ?? '0'));
        $currency = $interval['gmv']['currency'] ?? 'USD';

        return [
            'average_order_value' => [
                'amount' => $skuOrders > 0 ? $gmv / $skuOrders : 0,
                'currency' => $currency,
                'formatted' => $this->formatCurrency($skuOrders > 0 ? $gmv / $skuOrders : 0, $currency)
            ],
            'units_per_order' => $skuOrders > 0 ? round($unitsSold / $skuOrders, 2) : 0,
            'orders_per_customer' => $customers > 0 ? round($skuOrders / $customers, 2) : 0,
            'gmv_per_customer' => [
                'amount' => $customers > 0 ? $gmv / $customers : 0,
                'currency' => $currency,
                'formatted' => $this->formatCurrency($customers > 0 ? $gmv / $customers : 0, $currency)
            ],
            'conversion_efficiency' => $this->calculateLivesConversionEfficiency($interval)
        ];
    }

    /**
     * Calculate lives conversion efficiency score
     *
     * @param array $interval
     * @return float
     */
    private function calculateLivesConversionEfficiency(array $interval): float
    {
        $gmv = floatval($interval['gmv']['amount'] ?? 0);
        $skuOrders = intval($interval['sku_orders'] ?? 0);
        $customers = intval($interval['customers'] ?? 0);
        $unitsSold = intval($interval['units_sold'] ?? 0);
        $ctoRate = floatval(str_replace('%', '', $interval['click_to_order_rate'] ?? '0'));
        $ctr = floatval(str_replace('%', '', $interval['click_through_rate'] ?? '0'));

        $score = 0;
        
        if ($skuOrders > 0) {
            $score += min(($gmv / 100), 30); // GMV weight (capped at 30)
            $score += min(($skuOrders / 10), 25); // Orders weight (capped at 25)
            $score += min(($customers / 10), 20); // Customers weight (capped at 20)
            $score += min(($unitsSold / 10), 15); // Units weight (capped at 15)
            $score += min(($ctoRate / 10), 10); // CTO rate weight (capped at 10)
        }
        
        return round(min($score, 100), 2); // Cap at 100
    }

    /**
     * Transform TikTok Shop API response data
     *
     * @param array $data
     * @return array
     */
    private function transformLivesPerformanceData(array $data): array
    {
        if (!isset($data['data']['live_stream_sessions'])) {
            return $data['data'] ?? [];
        }

        $liveStreams = $data['data']['live_stream_sessions'];
        $transformedLiveStreams = [];

        foreach ($liveStreams as $live) {
            $transformedLiveStreams[] = [
                'live_id' => $live['id'] ?? null,
                'title' => $live['title'] ?? null,
                'username' => $live['username'] ?? null,
                'duration' => $this->calculateLiveDuration($live['start_time'] ?? null, $live['end_time'] ?? null),
                'sales_performance' => $this->transformSalesPerformance($live['sales_performance'] ?? []),
                'interaction_performance' => $this->transformInteractionPerformance($live['interaction_performance'] ?? []),
                'performance_metrics' => $this->calculateLivePerformanceMetrics($live)
            ];
        }

        return [
            'live_stream_sessions' => $transformedLiveStreams,
            'next_page_token' => $data['data']['next_page_token'] ?? null,
            'total_count' => $data['data']['total_count'] ?? 0,
            'latest_available_date' => $data['data']['latest_available_date'] ?? null
        ];
    }

    /**
     * Transform sales performance data
     *
     * @param array $salesData
     * @return array
     */
    private function transformSalesPerformance(array $salesData): array
    {
        return [
            'gmv' => [
                'amount' => floatval($salesData['gmv']['amount'] ?? 0),
                'currency' => $salesData['gmv']['currency'] ?? 'USD',
                'formatted' => $this->formatCurrency(
                    floatval($salesData['gmv']['amount'] ?? 0),
                    $salesData['gmv']['currency'] ?? 'USD'
                )
            ],
            'products_added' => [
                'count' => intval($salesData['products_added'] ?? 0),
                'formatted' => number_format($salesData['products_added'] ?? 0)
            ],
            'different_products_sold' => [
                'count' => intval($salesData['different_products_sold'] ?? 0),
                'formatted' => number_format($salesData['different_products_sold'] ?? 0)
            ],
            'created_sku_orders' => [
                'count' => intval($salesData['created_sku_orders'] ?? 0),
                'formatted' => number_format($salesData['created_sku_orders'] ?? 0)
            ],
            'sku_orders' => [
                'count' => intval($salesData['sku_orders'] ?? 0),
                'formatted' => number_format($salesData['sku_orders'] ?? 0)
            ],
            'units_sold' => [
                'count' => intval($salesData['unit_sold'] ?? 0),
                'formatted' => number_format($salesData['unit_sold'] ?? 0)
            ],
            'customers' => [
                'count' => intval($salesData['customers'] ?? 0),
                'formatted' => number_format($salesData['customers'] ?? 0)
            ],
            'avg_price' => [
                'amount' => floatval($salesData['avg_price']['amount'] ?? 0),
                'currency' => $salesData['avg_price']['currency'] ?? 'USD',
                'formatted' => $this->formatCurrency(
                    floatval($salesData['avg_price']['amount'] ?? 0),
                    $salesData['avg_price']['currency'] ?? 'USD'
                )
            ],
            'click_to_order_rate' => [
                'rate' => floatval(str_replace('%', '', $salesData['click_to_order_rate'] ?? '0')),
                'formatted' => $salesData['click_to_order_rate'] ?? '0%'
            ],
            '24h_live_gmv' => [
                'amount' => floatval($salesData['24h_live_gmv']['amount'] ?? 0),
                'currency' => $salesData['24h_live_gmv']['currency'] ?? 'USD',
                'formatted' => $this->formatCurrency(
                    floatval($salesData['24h_live_gmv']['amount'] ?? 0),
                    $salesData['24h_live_gmv']['currency'] ?? 'USD'
                )
            ]
        ];
    }

    /**
     * Transform interaction performance data
     *
     * @param array $interactionData
     * @return array
     */
    private function transformInteractionPerformance(array $interactionData): array
    {
        return [
            'acu' => [
                'count' => intval($interactionData['acu'] ?? 0),
                'formatted' => number_format($interactionData['acu'] ?? 0)
            ],
            'pcu' => [
                'count' => intval($interactionData['pcu'] ?? 0),
                'formatted' => number_format($interactionData['pcu'] ?? 0)
            ],
            'viewers' => [
                'count' => intval($interactionData['viewers'] ?? 0),
                'formatted' => number_format($interactionData['viewers'] ?? 0)
            ],
            'views' => [
                'count' => intval($interactionData['views'] ?? 0),
                'formatted' => number_format($interactionData['views'] ?? 0)
            ],
            'avg_viewing_duration' => [
                'seconds' => intval($interactionData['avg_viewing_duration'] ?? 0),
                'formatted' => $this->formatDuration(intval($interactionData['avg_viewing_duration'] ?? 0))
            ],
            'comments' => [
                'count' => intval($interactionData['comments'] ?? 0),
                'formatted' => number_format($interactionData['comments'] ?? 0)
            ],
            'shares' => [
                'count' => intval($interactionData['shares'] ?? 0),
                'formatted' => number_format($interactionData['shares'] ?? 0)
            ],
            'likes' => [
                'count' => intval($interactionData['likes'] ?? 0),
                'formatted' => number_format($interactionData['likes'] ?? 0)
            ],
            'new_followers' => [
                'count' => intval($interactionData['new_followers'] ?? 0),
                'formatted' => number_format($interactionData['new_followers'] ?? 0)
            ],
            'product_impressions' => [
                'count' => intval($interactionData['product_impressions'] ?? 0),
                'formatted' => number_format($interactionData['product_impressions'] ?? 0)
            ],
            'product_clicks' => [
                'count' => intval($interactionData['product_clicks'] ?? 0),
                'formatted' => number_format($interactionData['product_clicks'] ?? 0)
            ],
            'click_through_rate' => [
                'rate' => floatval(str_replace('%', '', $interactionData['click_through_rate'] ?? '0')),
                'formatted' => $interactionData['click_through_rate'] ?? '0%'
            ]
        ];
    }

    /**
     * Calculate live duration
     *
     * @param string|null $startTime
     * @param string|null $endTime
     * @return array
     */
    private function calculateLiveDuration(?string $startTime, ?string $endTime): array
    {
        if (!$startTime || !$endTime) {
            return [
                'seconds' => 0,
                'formatted' => '0m 0s',
                'start_time' => null,
                'end_time' => null
            ];
        }

        $start = intval($startTime);
        $end = intval($endTime);
        $duration = $end - $start;

        return [
            'seconds' => $duration,
            'formatted' => $this->formatDuration($duration),
            'start_time' => [
                'timestamp' => $start,
                'formatted' => date('Y-m-d H:i:s', $start)
            ],
            'end_time' => [
                'timestamp' => $end,
                'formatted' => date('Y-m-d H:i:s', $end)
            ]
        ];
    }

    /**
     * Calculate live performance metrics
     *
     * @param array $live
     * @return array
     */
    private function calculateLivePerformanceMetrics(array $live): array
    {
        $sales = $live['sales_performance'] ?? [];
        $interaction = $live['interaction_performance'] ?? [];

        $gmv = floatval($sales['gmv']['amount'] ?? 0);
        $views = intval($interaction['views'] ?? 0);
        $customers = intval($sales['customers'] ?? 0);
        $skuOrders = intval($sales['sku_orders'] ?? 0);
        $currency = $sales['gmv']['currency'] ?? 'USD';

        return [
            'gmv_per_view' => [
                'amount' => $views > 0 ? $gmv / $views : 0,
                'currency' => $currency,
                'formatted' => $this->formatCurrency($views > 0 ? $gmv / $views : 0, $currency)
            ],
            'conversion_rate' => $views > 0 ? round(($customers / $views) * 100, 2) : 0,
            'order_rate' => $views > 0 ? round(($skuOrders / $views) * 100, 2) : 0,
            'engagement_score' => $this->calculateLiveEngagementScore($interaction),
            'sales_efficiency' => $this->calculateSalesEfficiency($sales, $interaction)
        ];
    }

    /**
     * Calculate live engagement score
     *
     * @param array $interaction
     * @return float
     */
    private function calculateLiveEngagementScore(array $interaction): float
    {
        $views = intval($interaction['views'] ?? 0);
        $likes = intval($interaction['likes'] ?? 0);
        $comments = intval($interaction['comments'] ?? 0);
        $shares = intval($interaction['shares'] ?? 0);

        if ($views === 0) return 0;

        $engagementRate = ($likes + $comments + $shares) / $views;
        return round(min($engagementRate * 100, 100), 2);
    }

    /**
     * Calculate sales efficiency score
     *
     * @param array $sales
     * @param array $interaction
     * @return float
     */
    private function calculateSalesEfficiency(array $sales, array $interaction): float
    {
        $gmv = floatval($sales['gmv']['amount'] ?? 0);
        $views = intval($interaction['views'] ?? 0);
        $customers = intval($sales['customers'] ?? 0);
        $ctr = floatval(str_replace('%', '', $interaction['click_through_rate'] ?? '0'));

        $score = 0;
        
        if ($views > 0) {
            $score += min(($gmv / 1000), 40); // GMV weight (capped at 40)
            $score += min(($customers / 100), 30); // Customers weight (capped at 30)
            $score += min(($ctr / 10), 20); // CTR weight (capped at 20)
            $score += min(($views / 10000), 10); // Views weight (capped at 10)
        }
        
        return round(min($score, 100), 2);
    }

    /**
     * Calculate lives summary
     *
     * @param array $liveStreams
     * @return array
     */
    private function calculateLivesSummary(array $liveStreams): array
    {
        $totalGmv = 0;
        $totalViews = 0;
        $totalCustomers = 0;
        $totalOrders = 0;
        $totalUnits = 0;
        $totalLikes = 0;
        $totalComments = 0;
        $totalShares = 0;
        $totalDuration = 0;
        $liveCount = count($liveStreams);

        foreach ($liveStreams as $live) {
            $sales = $live['sales_performance'] ?? [];
            $interaction = $live['interaction_performance'] ?? [];
            $duration = $live['duration'] ?? [];

            $totalGmv += $sales['gmv']['amount'] ?? 0;
            $totalViews += $interaction['views']['count'] ?? 0;
            $totalCustomers += $sales['customers']['count'] ?? 0;
            $totalOrders += $sales['sku_orders']['count'] ?? 0;
            $totalUnits += $sales['units_sold']['count'] ?? 0;
            $totalLikes += $interaction['likes']['count'] ?? 0;
            $totalComments += $interaction['comments']['count'] ?? 0;
            $totalShares += $interaction['shares']['count'] ?? 0;
            $totalDuration += $duration['seconds'] ?? 0;
        }

        return [
            'total_gmv' => [
                'amount' => $totalGmv,
                'currency' => 'USD',
                'formatted' => $this->formatCurrency($totalGmv, 'USD')
            ],
            'total_views' => $totalViews,
            'total_customers' => $totalCustomers,
            'total_orders' => $totalOrders,
            'total_units_sold' => $totalUnits,
            'total_engagement' => $totalLikes + $totalComments + $totalShares,
            'average_gmv_per_live' => [
                'amount' => $liveCount > 0 ? $totalGmv / $liveCount : 0,
                'currency' => 'USD',
                'formatted' => $this->formatCurrency($liveCount > 0 ? $totalGmv / $liveCount : 0, 'USD')
            ],
            'average_views_per_live' => $liveCount > 0 ? round($totalViews / $liveCount, 2) : 0,
            'average_customers_per_live' => $liveCount > 0 ? round($totalCustomers / $liveCount, 2) : 0,
            'average_duration_per_live' => [
                'seconds' => $liveCount > 0 ? round($totalDuration / $liveCount) : 0,
                'formatted' => $this->formatDuration($liveCount > 0 ? round($totalDuration / $liveCount) : 0)
            ],
            'total_live_streams' => $liveCount
        ];
    }

    /**
     * Format duration in seconds to human readable format
     *
     * @param int $seconds
     * @return string
     */
    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $remainingSeconds);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $remainingSeconds);
        } else {
            return sprintf('%ds', $remainingSeconds);
        }
    }

    /**
     * Format currency amount
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    private function formatCurrency(float $amount, string $currency): string
    {
        $formatters = [
            'USD' => '$%.2f',
            'VND' => '%.0f ₫',
            'EUR' => '€%.2f',
            'GBP' => '£%.2f',
        ];

        $format = $formatters[$currency] ?? '%.2f %s';
        
        if (strpos($format, '%s') !== false) {
            return sprintf($format, $amount, $currency);
        }
        
        return sprintf($format, $amount);
    }

    /**
     * Generate signature for TikTok Shop API
     *
     * @param array $params
     * @param string $secret
     * @return string
     */
    private function generateSignature(array $params, string $secret): string
    {
        // Simplified signature generation
        // In production, implement proper HMAC-SHA256
        ksort($params);
        $queryString = http_build_query($params);
        return hash('sha256', $queryString . $secret);
    }

    /**
     * Return success response
     *
     * @param mixed $data
     * @param int $statusCode
     * @return JsonResponse
     */
    private function successResponse($data, int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Return error response
     *
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    private function errorResponse(string $message, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $statusCode);
    }
}
