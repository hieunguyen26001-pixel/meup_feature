<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Services\TikTokShopTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CancellationApiController extends Controller
{
    protected $tokenService;

    public function __construct(TikTokShopTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Get cancellations from TikTok Shop API
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

            // Fetch cancellations from TikTok API
            try {
                $cancellations = $this->fetchCancellationsFromTikTok($shopId, $request);
            } catch (\Exception $e) {
                Log::error('Cancellations API failed', [
                    'shop_id' => $shopId,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Lỗi khi tải dữ liệu cancellations từ TikTok Shop: ' . $e->getMessage(),
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
                    'cancellations' => $cancellations['cancellations'] ?? [],
                    'total_count' => $cancellations['total_count'] ?? 0,
                    'next_page_token' => $cancellations['next_page_token'] ?? null
                ],
                'shop_id' => $shopId,
                'source' => 'tiktok_api'
            ]);

        } catch (\Exception $e) {
            Log::error('Cancellation API error', [
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
     * Fetch cancellations from TikTok Shop API
     * @throws \Exception
     */
    public function fetchCancellationsFromTikTok(string $shopId, Request $request): array
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
            'sort_field' => $request->get('sort_field', 'update_time'),
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
        if ($request->has('cancel_ids') && !empty($request->get('cancel_ids'))) {
            $bodyData['cancel_ids'] = $request->get('cancel_ids');
        }

        if ($request->has('order_ids') && !empty($request->get('order_ids'))) {
            $bodyData['order_ids'] = $request->get('order_ids');
        }

        if ($request->has('buyer_user_ids') && !empty($request->get('buyer_user_ids'))) {
            $bodyData['buyer_user_ids'] = $request->get('buyer_user_ids');
        }

        if ($request->has('cancel_types') && !empty($request->get('cancel_types'))) {
            $bodyData['cancel_types'] = $request->get('cancel_types');
        }

        if ($request->has('cancel_status') && !empty($request->get('cancel_status'))) {
            $bodyData['cancel_status'] = $request->get('cancel_status');
        }

        $body = json_encode($bodyData);

        // Generate signature using tts_sign method
        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/return_refund/202309/cancellations/search',
            $body,
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        $url = 'https://open-api.tiktokglobalshop.com/return_refund/202309/cancellations/search?' . http_build_query($signatureData['signed_query']);

        // Make API request
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-tts-access-token' => $token->access_token,
        ])->withBody($body, 'application/json')
        ->post($url);

        if (!$response->successful()) {
            Log::error('TikTok Cancellations API failed', [
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
        Log::info('TikTok Cancellations API response', [
            'code' => $data['code'] ?? 'unknown',
            'message' => $data['message'] ?? 'unknown',
            'cancellations_count' => count($data['data']['cancellations'] ?? []),
            'total_count' => $data['data']['total_count'] ?? 0
        ]);
        
        if (!$data || !isset($data['code']) || $data['code'] !== 0) {
            throw new \Exception('API trả về lỗi: ' . json_encode($data));
        }

        return $data['data'];
    }

    /**
     * Get cancellation details by ID
     */
    public function show(Request $request)
    {
        try {
            $cancelId = $request->get('cancel_id');
            if (!$cancelId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cancel ID is required'
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

            // Get cancellation details from TikTok API
            $cancellationDetails = $this->fetchCancellationDetailsFromTikTok($shopId, $cancelId);

            return response()->json([
                'success' => true,
                'data' => $cancellationDetails,
                'shop_id' => $shopId,
                'source' => 'tiktok_api'
            ]);

        } catch (\Exception $e) {
            Log::error('Cancellation details API error', [
                'error' => $e->getMessage(),
                'cancel_id' => $request->get('cancel_id'),
                'shop_id' => $request->get('shop_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Lỗi khi lấy chi tiết cancellation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch cancellation details from TikTok Shop API
     * @throws \Exception
     */
    public function fetchCancellationDetailsFromTikTok(string $shopId, string $cancelId): array
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

        // Build parameters for Cancellation Details API
        $params = [
            'shop_cipher' => $shop->seller_cipher,
            'cancel_id' => $cancelId,
            'app_key' => config('services.tiktok_shop.client_key'),
            'timestamp' => time(),
        ];

        // Generate signature using tts_sign method
        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/return_refund/202309/cancellations',
            null,
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        // Use Cancellation Details API endpoint with specific cancel ID
        $url = 'https://open-api.tiktokglobalshop.com/return_refund/202309/cancellations?' . http_build_query($signatureData['signed_query']);

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
     * Get cancellation statistics
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

            // Get cancellations data
            $cancellations = $this->fetchCancellationsFromTikTok($shopId, $request);
            $cancellationsList = $cancellations['cancellations'] ?? [];

            // Calculate statistics
            $stats = [
                'total_cancellations' => count($cancellationsList),
                'total_refund_amount' => 0,
                'cancel_type_counts' => [],
                'cancel_status_counts' => [],
                'currency_counts' => [],
            ];

            foreach ($cancellationsList as $cancellation) {
                // Count by cancel type
                $cancelType = $cancellation['cancel_type'] ?? 'UNKNOWN';
                $stats['cancel_type_counts'][$cancelType] = ($stats['cancel_type_counts'][$cancelType] ?? 0) + 1;

                // Count by cancel status
                $cancelStatus = $cancellation['cancel_status'] ?? 'UNKNOWN';
                $stats['cancel_status_counts'][$cancelStatus] = ($stats['cancel_status_counts'][$cancelStatus] ?? 0) + 1;

                // Sum total refund amount
                if (isset($cancellation['refund_amount']['refund_total'])) {
                    $stats['total_refund_amount'] += (float) $cancellation['refund_amount']['refund_total'];
                }

                // Count by currency
                $currency = $cancellation['refund_amount']['currency'] ?? 'UNKNOWN';
                $stats['currency_counts'][$currency] = ($stats['currency_counts'][$currency] ?? 0) + 1;
            }

            return response()->json([
                'success' => true,
                'data' => $stats,
                'shop_id' => $shopId
            ]);

        } catch (\Exception $e) {
            Log::error('Cancellation stats error', [
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