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

class ShopSkusAnalyticsController extends Controller
{
    use ApiResponseTrait;

    private TikTokShopTokenService $tokenService;

    public function __construct(TikTokShopTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Get shop SKUs performance analytics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSkusPerformance(Request $request): JsonResponse
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
            $productId = $request->get('product_id');
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
            $endpoint = '/analytics/202406/shop_skus/performance';
            
            // Build query parameters
            $queryParams = [
                'sort_field' => $sortField,
                'page_size' => $pageSize,
                'currency' => $currency,
                'end_date_lt' => $request->get('end_date_lt'),
                'start_date_ge' => $request->get('start_date_ge'),
                'sort_order' => $sortOrder,
                'shop_cipher' => $request->get('shop_cipher'),
                'app_key' => $request->get('app_key'),
                'timestamp' => $timestamp,
            ];

            // Add optional parameters
            if ($pageToken) {
                $queryParams['page_token'] = $pageToken;
            }
            if ($productId) {
                $queryParams['product_id'] = $productId;
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
                Log::error('TikTok Shop SKUs Analytics API Error', [
                    'shop_id' => $shop->shop_id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'query_params' => $queryParams
                ]);

                return $this->errorResponse(
                    'Lỗi khi gọi TikTok Shop API: ' . $response->status(),
                    500,
                    [
                        'shop_id' => $shop->shop_id,
                        'error_type' => 'TikTok_API_Error',
                        'suggestion' => 'Kiểm tra shop_cipher và app_key có đúng không'
                    ]
                );
            }

            $data = $response->json();

            // Transform response data
            $transformedData = $this->transformSkusPerformanceData($data);

            return $this->successResponse([
                'skus_performance' => $transformedData['skus'] ?? [],
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
                    'product_id' => $productId
                ]
            ], $shop->shop_id, 'tiktok_api');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Dữ liệu đầu vào không hợp lệ: ' . implode(', ', $e->errors()), 422);
        } catch (\Exception $e) {
            Log::error('Shop SKUs Analytics Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return $this->errorResponse(
                'Lỗi hệ thống: ' . $e->getMessage(),
                500,
                [
                    'error_type' => 'System_Error',
                    'suggestion' => 'Vui lòng thử lại sau hoặc liên hệ admin'
                ]
            );
        }
    }

    /**
     * Get SKU performance summary
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSkusSummary(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start_date_ge' => 'required|date|date_format:Y-m-d',
                'end_date_lt' => 'required|date|date_format:Y-m-d|after:start_date_ge',
                'shop_cipher' => 'required|string',
                'app_key' => 'required|string',
            ]);

            // Get all SKUs data (with large page size)
            $request->merge(['page_size' => 100]);
            $performanceResponse = $this->getSkusPerformance($request);
            
            if (!$performanceResponse->getData()->success) {
                return $performanceResponse;
            }

            $data = $performanceResponse->getData()->data;
            $skus = $data->skus_performance ?? [];

            // Calculate summary statistics
            $summary = $this->calculateSkusSummary($skus);

            return $this->successResponse([
                'summary' => $summary,
                'date_range' => $data->date_range,
                'total_skus' => count($skus)
            ], $data->shop_id ?? null, 'tiktok_api');

        } catch (\Exception $e) {
            Log::error('Shop SKUs Summary Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Lỗi khi tính toán summary: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get top performing SKUs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTopSkus(Request $request): JsonResponse
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
            
            // Get SKUs performance data
            $request->merge([
                'page_size' => $limit,
                'sort_field' => 'gmv',
                'sort_order' => 'DESC'
            ]);
            
            $performanceResponse = $this->getSkusPerformance($request);
            
            if (!$performanceResponse->getData()->success) {
                return $performanceResponse;
            }

            $data = $performanceResponse->getData()->data;
            $skus = $data->skus_performance ?? [];

            // Add ranking
            $rankedSkus = array_map(function($sku, $index) {
                $sku['rank'] = $index + 1;
                return $sku;
            }, $skus, array_keys($skus));

            return $this->successResponse([
                'top_skus' => $rankedSkus,
                'date_range' => $data->date_range,
                'total_found' => count($skus)
            ], $data->shop_id ?? null, 'tiktok_api');

        } catch (\Exception $e) {
            Log::error('Top SKUs Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Lỗi khi lấy top SKUs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Transform TikTok Shop API response data
     *
     * @param array $data
     * @return array
     */
    private function transformSkusPerformanceData(array $data): array
    {
        if (!isset($data['data']['skus'])) {
            return $data['data'] ?? [];
        }

        $skus = $data['data']['skus'];
        $transformedSkus = [];

        foreach ($skus as $sku) {
            $transformedSkus[] = [
                'sku_id' => $sku['id'] ?? null,
                'product_id' => $sku['product_id'] ?? null,
                'gmv' => [
                    'amount' => floatval($sku['gmv']['amount'] ?? 0),
                    'currency' => $sku['gmv']['currency'] ?? 'USD',
                    'formatted' => $this->formatCurrency(
                        floatval($sku['gmv']['amount'] ?? 0),
                        $sku['gmv']['currency'] ?? 'USD'
                    )
                ],
                'orders' => [
                    'count' => intval($sku['sku_orders'] ?? 0),
                    'formatted' => number_format($sku['sku_orders'] ?? 0)
                ],
                'units_sold' => [
                    'count' => intval($sku['units_sold'] ?? 0),
                    'formatted' => number_format($sku['units_sold'] ?? 0)
                ],
                'average_order_value' => $this->calculateAOV($sku),
                'conversion_rate' => $this->calculateConversionRate($sku)
            ];
        }

        return [
            'skus' => $transformedSkus,
            'next_page_token' => $data['data']['next_page_token'] ?? null,
            'total_count' => $data['data']['total_count'] ?? 0,
            'latest_available_date' => $data['data']['latest_available_date'] ?? null
        ];
    }

    /**
     * Calculate summary statistics for SKUs
     *
     * @param array $skus
     * @return array
     */
    private function calculateSkusSummary(array $skus): array
    {
        if (empty($skus)) {
            return [
                'total_gmv' => ['amount' => 0, 'currency' => 'USD', 'formatted' => '$0.00'],
                'total_orders' => 0,
                'total_units_sold' => 0,
                'average_gmv_per_sku' => ['amount' => 0, 'currency' => 'USD', 'formatted' => '$0.00'],
                'average_orders_per_sku' => 0,
                'average_units_per_sku' => 0,
                'top_sku' => null,
                'total_skus' => 0
            ];
        }

        $totalGmv = 0;
        $totalOrders = 0;
        $totalUnits = 0;
        $topSku = null;
        $maxGmv = 0;

        foreach ($skus as $sku) {
            $gmv = $sku['gmv']['amount'] ?? 0;
            $orders = $sku['orders']['count'] ?? 0;
            $units = $sku['units_sold']['count'] ?? 0;

            $totalGmv += $gmv;
            $totalOrders += $orders;
            $totalUnits += $units;

            if ($gmv > $maxGmv) {
                $maxGmv = $gmv;
                $topSku = $sku;
            }
        }

        $skuCount = count($skus);
        $currency = $skus[0]['gmv']['currency'] ?? 'USD';

        return [
            'total_gmv' => [
                'amount' => $totalGmv,
                'currency' => $currency,
                'formatted' => $this->formatCurrency($totalGmv, $currency)
            ],
            'total_orders' => $totalOrders,
            'total_units_sold' => $totalUnits,
            'average_gmv_per_sku' => [
                'amount' => $skuCount > 0 ? $totalGmv / $skuCount : 0,
                'currency' => $currency,
                'formatted' => $this->formatCurrency($skuCount > 0 ? $totalGmv / $skuCount : 0, $currency)
            ],
            'average_orders_per_sku' => $skuCount > 0 ? round($totalOrders / $skuCount, 2) : 0,
            'average_units_per_sku' => $skuCount > 0 ? round($totalUnits / $skuCount, 2) : 0,
            'top_sku' => $topSku,
            'total_skus' => $skuCount
        ];
    }

    /**
     * Calculate Average Order Value for SKU
     *
     * @param array $sku
     * @return array
     */
    private function calculateAOV(array $sku): array
    {
        $gmv = floatval($sku['gmv']['amount'] ?? 0);
        $orders = intval($sku['sku_orders'] ?? 0);
        $currency = $sku['gmv']['currency'] ?? 'USD';

        $aov = $orders > 0 ? $gmv / $orders : 0;

        return [
            'amount' => $aov,
            'currency' => $currency,
            'formatted' => $this->formatCurrency($aov, $currency)
        ];
    }

    /**
     * Calculate conversion rate for SKU
     *
     * @param array $sku
     * @return float
     */
    private function calculateConversionRate(array $sku): float
    {
        $orders = intval($sku['sku_orders'] ?? 0);
        $units = intval($sku['units_sold'] ?? 0);

        return $units > 0 ? round(($orders / $units) * 100, 2) : 0;
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
