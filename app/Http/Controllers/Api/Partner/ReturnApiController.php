<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Services\TikTokShopTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReturnApiController extends Controller
{
    protected $tokenService;

    public function __construct(TikTokShopTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Get returns/refunds from TikTok Shop API
     */
    public function index(Request $request)
    {
        try {
            // Get shop ID from request or use first available shop
            $shopId = $request->get('shop_id');
            if (!$shopId) {
                $shops = Shop::where('is_active', true)->get();
                if ($shops->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Không có shop nào được ủy quyền'
                    ], 400);
                }
                $shopId = $shops->first()->shop_id;
            }

            // Get pagination parameters
            $pageSize = (int) $request->get('page_size', 10);
            $pageToken = $request->get('page_token');

            // Process date range from frontend
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            
            if ($startDate) {
                $request->merge(['create_time_ge' => strtotime($startDate)]);
                $request->merge(['update_time_ge' => strtotime($startDate)]);
            }
            
            if ($endDate) {
                $request->merge(['create_time_lt' => strtotime($endDate . ' 23:59:59')]);
                $request->merge(['update_time_lt' => strtotime($endDate . ' 23:59:59')]);
            }

            // Fetch returns from TikTok API
            try {
                $returns = $this->fetchReturnsFromTikTok($shopId, $request);
            } catch (\Exception $e) {
                Log::error('Returns API failed', [
                    'shop_id' => $shopId,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Lỗi khi tải dữ liệu returns từ TikTok Shop: ' . $e->getMessage(),
                    'details' => [
                        'shop_id' => $shopId,
                        'error_type' => get_class($e),
                        'suggestion' => 'Vui lòng kiểm tra token và shop_cipher'
                    ],
                    'shop_id' => $shopId
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'return_orders' => $returns['return_orders'] ?? [],
                    'total_count' => $returns['total_count'] ?? 0,
                    'next_page_token' => $returns['next_page_token'] ?? null
                ],
                'shop_id' => $shopId,
                'source' => 'tiktok_api'
            ]);

        } catch (\Exception $e) {
            Log::error('Return API error', [
                'error' => $e->getMessage(),
                'shop_id' => $shopId ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch returns from TikTok Shop API
     * @throws \Exception
     */
    public function fetchReturnsFromTikTok(string $shopId, Request $request): array
    {
        // Get token
        $token = $this->tokenService->getValidToken($shopId);
        if (!$token) {
            throw new \Exception('Không có token hợp lệ. Vui lòng ủy quyền lại.');
        }

        // Get shop info
        $shop = Shop::where('shop_id', $shopId)->first();
        if (!$shop) {
            throw new \Exception("Shop {$shopId} không tồn tại");
        }

        // Build parameters
        $params = [
            'shop_cipher' => $shop->seller_cipher,
            'page_size' => (int) $request->get('page_size', 10),
            'app_key' => config('services.tiktok_shop.client_key'),
            'timestamp' => time(),
            'sort_field' => $request->get('sort_field', 'create_time'),
            'sort_order' => $request->get('sort_order', 'ASC'),
        ];

        if ($request->filled('page_token')) {
            $params['page_token'] = $request->get('page_token');
        }

        // Build request body data
        $bodyData = [
            'create_time_ge' => (int) $request->get('create_time_ge', strtotime('-30 days')),
            'create_time_lt' => (int) $request->get('create_time_lt', time()),
            'update_time_ge' => (int) $request->get('update_time_ge', strtotime('-30 days')),
            'update_time_lt' => (int) $request->get('update_time_lt', time()),
            'locale' => $request->get('locale', 'en-US'),
        ];

        // Add optional filters
        if ($request->has('return_ids') && !empty($request->get('return_ids'))) {
            $bodyData['return_ids'] = $request->get('return_ids');
        }

        if ($request->has('order_ids') && !empty($request->get('order_ids'))) {
            $bodyData['order_ids'] = $request->get('order_ids');
        }

        if ($request->has('buyer_user_ids') && !empty($request->get('buyer_user_ids'))) {
            $bodyData['buyer_user_ids'] = $request->get('buyer_user_ids');
        }

        if ($request->has('return_types') && !empty($request->get('return_types'))) {
            $bodyData['return_types'] = $request->get('return_types');
        }

        if ($request->has('return_status') && !empty($request->get('return_status'))) {
            $bodyData['return_status'] = $request->get('return_status');
        }

        if ($request->has('seller_proposed_return_type') && !empty($request->get('seller_proposed_return_type'))) {
            $bodyData['seller_proposed_return_type'] = $request->get('seller_proposed_return_type');
        }

        if ($request->has('arbitration_status') && !empty($request->get('arbitration_status'))) {
            $bodyData['arbitration_status'] = $request->get('arbitration_status');
        }

        $body = json_encode($bodyData);

        // Generate signature using tts_sign method
        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/return_refund/202309/returns/search',
            $body,
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        $url = 'https://open-api.tiktokglobalshop.com/return_refund/202309/returns/search?' . http_build_query($signatureData['signed_query']);

        // Make API request
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-tts-access-token' => $token->access_token,
        ])->withBody($body, 'application/json')
        ->post($url);

        if (!$response->successful()) {
            Log::error('TikTok Returns API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $url,
                'params' => $params,
                'body_data' => $bodyData
            ]);
            
            $errorData = $response->json();
            if (isset($errorData['code']) && $errorData['code'] == 106011) {
                throw new \Exception('Shop cipher không hợp lệ. Vui lòng ủy quyền lại shop để lấy shop_cipher thực từ TikTok Shop.');
            }
            
            throw new \Exception('Yêu cầu API thất bại: ' . $response->body());
        }

        $data = $response->json();
        Log::info('TikTok Returns API response', [
            'code' => $data['code'] ?? 'unknown',
            'message' => $data['message'] ?? 'unknown',
            'returns_count' => count($data['data']['return_orders'] ?? []),
            'total_count' => $data['data']['total_count'] ?? 0
        ]);
        
        if (!$data || !isset($data['code']) || $data['code'] !== 0) {
            throw new \Exception('API trả về lỗi: ' . json_encode($data));
        }

        return $data['data'];
    }

    /**
     * Get return details by ID
     */
    public function show(Request $request)
    {
        try {
            $returnId = $request->get('return_id');
            if (!$returnId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Return ID is required'
                ], 400);
            }

            $shopId = $request->get('shop_id');
            if (!$shopId) {
                $shops = Shop::where('is_active', true)->get();
                if ($shops->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Không có shop nào được ủy quyền'
                    ], 400);
                }
                $shopId = $shops->first()->shop_id;
            }

            // Get return details from TikTok API
            $returnDetails = $this->fetchReturnDetailsFromTikTok($shopId, $returnId);

            return response()->json([
                'success' => true,
                'data' => $returnDetails,
                'shop_id' => $shopId,
                'source' => 'tiktok_api'
            ]);

        } catch (\Exception $e) {
            Log::error('Return details API error', [
                'error' => $e->getMessage(),
                'return_id' => $request->get('return_id'),
                'shop_id' => $request->get('shop_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Lỗi khi lấy chi tiết return: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch return details from TikTok Shop API
     * @throws \Exception
     */
    public function fetchReturnDetailsFromTikTok(string $shopId, string $returnId): array
    {
        // Get token
        $token = $this->tokenService->getValidToken($shopId);
        if (!$token) {
            throw new \Exception('Không có token hợp lệ. Vui lòng ủy quyền lại.');
        }

        // Get shop info
        $shop = Shop::where('shop_id', $shopId)->first();
        if (!$shop) {
            throw new \Exception("Shop {$shopId} không tồn tại");
        }

        // Build parameters for Return Details API
        $params = [
            'shop_cipher' => $shop->seller_cipher,
            'return_id' => $returnId,
            'app_key' => config('services.tiktok_shop.client_key'),
            'timestamp' => time(),
        ];

        // Generate signature using tts_sign method
        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/return_refund/202309/returns',
            null,
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        // Use Return Details API endpoint with specific return ID
        $url = 'https://open-api.tiktokglobalshop.com/return_refund/202309/returns?' . http_build_query($signatureData['signed_query']);

        // Make API request
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-tts-access-token' => $token->access_token,
        ])->get($url);

        if (!$response->successful()) {
            throw new \Exception('Yêu cầu API thất bại: ' . $response->body());
        }

        $data = $response->json();
        
        if (!$data || !isset($data['code']) || $data['code'] !== 0) {
            throw new \Exception('API trả về lỗi: ' . json_encode($data));
        }

        return $data;
    }

    /**
     * Get return statistics
     */
    public function stats(Request $request)
    {
        try {
            $shopId = $request->get('shop_id');
            if (!$shopId) {
                $shops = Shop::where('is_active', true)->get();
                if ($shops->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Không có shop nào được ủy quyền'
                    ], 400);
                }
                $shopId = $shops->first()->shop_id;
            }

            // Get returns data
            $returns = $this->fetchReturnsFromTikTok($shopId, $request);
            $returnsList = $returns['return_orders'] ?? [];

            // Calculate statistics
            $stats = [
                'total_returns' => count($returnsList),
                'total_refund_amount' => 0,
                'return_type_counts' => [],
                'return_status_counts' => [],
                'arbitration_status_counts' => [],
                'currency_counts' => [],
            ];

            foreach ($returnsList as $return) {
                // Count by return type
                $returnType = $return['return_type'] ?? 'UNKNOWN';
                $stats['return_type_counts'][$returnType] = ($stats['return_type_counts'][$returnType] ?? 0) + 1;

                // Count by return status
                $returnStatus = $return['return_status'] ?? 'UNKNOWN';
                $stats['return_status_counts'][$returnStatus] = ($stats['return_status_counts'][$returnStatus] ?? 0) + 1;

                // Count by arbitration status
                $arbitrationStatus = $return['arbitration_status'] ?? 'UNKNOWN';
                $stats['arbitration_status_counts'][$arbitrationStatus] = ($stats['arbitration_status_counts'][$arbitrationStatus] ?? 0) + 1;

                // Sum total refund amount
                if (isset($return['refund_amount']['refund_total'])) {
                    $stats['total_refund_amount'] += (float) $return['refund_amount']['refund_total'];
                }

                // Count by currency
                $currency = $return['refund_amount']['currency'] ?? 'UNKNOWN';
                $stats['currency_counts'][$currency] = ($stats['currency_counts'][$currency] ?? 0) + 1;
            }

            return response()->json([
                'success' => true,
                'data' => $stats,
                'shop_id' => $shopId
            ]);

        } catch (\Exception $e) {
            Log::error('Return stats error', [
                'error' => $e->getMessage(),
                'shop_id' => $shopId ?? null
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
