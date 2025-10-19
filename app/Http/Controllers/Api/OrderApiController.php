<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Services\TikTokShopTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderApiController extends Controller
{
    protected $tokenService;

    public function __construct(TikTokShopTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Get orders from TikTok Shop API
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
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 20);
            $pageToken = $request->get('page_token');

            // Fetch orders from TikTok API
            try {
                $orders = $this->fetchOrdersFromTikTok($shopId, $request);
                Log::info('Orders API success from TikTok', [
                    'shop_id' => $shopId,
                    'orders_count' => count($orders['data']['orders'] ?? [])
                ]);
            } catch (\Exception $e) {
                Log::error('Orders API failed', [
                    'shop_id' => $shopId,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Lỗi khi tải đơn hàng từ TikTok Shop: ' . $e->getMessage(),
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
                'data' => $orders,
                'shop_id' => $shopId,
                'source' => 'tiktok_api'
            ]);

        } catch (\Exception $e) {
            Log::error('Order API error', [
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
     * Fetch orders from TikTok Shop API
     * @throws \Exception
     */
    public function fetchOrdersFromTikTok(string $shopId, Request $request): array
    {
        // Get token
        $token = $this->tokenService->getValidToken($shopId);
        if (!$token) throw new \Exception('Không có token hợp lệ. Vui lòng ủy quyền lại.');

        // Get shop info
        $shop = Shop::where('shop_id', $shopId)->first();
        if (!$shop) {
            throw new \Exception("Shop {$shopId} không tồn tại");
        }

        // Build parameters
        $params = [
            'shop_cipher' => $shop->seller_cipher,
            'page_size' => (int) $request->get('page_size', 20),
            'app_key' => config('services.tiktok_shop.client_key'),
            'timestamp' => time(),
            'sort_field' => $request->get('sort_field', 'create_time'),
            'sort_order' => $request->get('sort_order', 'DESC'),
        ];

        if ($request->filled('page_token')) {
            $params['page_token'] = $request->get('page_token');
        }

        // Generate signature using tts_sign method
        $body = json_encode([
            'create_time_ge' => (int) $request->get('create_time_ge', strtotime('-7 days')),
            'create_time_lt' => (int) $request->get('create_time_lt', time()),
            'update_time_ge' => (int) $request->get('update_time_ge', strtotime('-7 days')),
            'update_time_lt' => (int) $request->get('update_time_lt', time()),
            'shipping_type' => $request->get('shipping_type', 'TIKTOK'),
            'is_buyer_request_cancel' => $request->get('is_buyer_request_cancel', false),
        ]);

        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/order/202309/orders/search',
            $body,
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        $url = 'https://open-api.tiktokglobalshop.com/order/202309/orders/search?' . http_build_query($signatureData['signed_query']);

        // Make API request
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-tts-access-token' => $token->access_token,
        ])->withBody($body, 'application/json')
        ->post($url);

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
     * Get order details by ID
     */
    public function show(Request $request)
    {
        try {
            $orderId = $request->get('order_id');
            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Order ID is required'
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

            // Get order details from TikTok API
            $orderDetails = $this->fetchOrderDetailsFromTikTok($shopId, $orderId);

            return response()->json([
                'success' => true,
                'data' => $orderDetails,
                'shop_id' => $shopId,
                'source' => 'tiktok_api'
            ]);

        } catch (\Exception $e) {
            Log::error('Order details API error', [
                'error' => $e->getMessage(),
                'order_id' => $request->get('order_id'),
                'shop_id' => $request->get('shop_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Lỗi khi lấy chi tiết đơn hàng: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch order details from TikTok Shop API
     * @throws \Exception
     */
    public function fetchOrderDetailsFromTikTok(string $shopId, string $orderId): array
    {
        // Get token
        $token = $this->tokenService->getValidToken($shopId);
        if (!$token) throw new \Exception('Không có token hợp lệ. Vui lòng ủy quyền lại.');

        // Get shop info
        $shop = Shop::where('shop_id', $shopId)->first();
        if (!$shop) {
            throw new \Exception("Shop {$shopId} không tồn tại");
        }

        // Build parameters for Order Details API
        $params = [
            'shop_cipher' => $shop->seller_cipher,
            'ids' => $orderId,
            'app_key' => config('services.tiktok_shop.client_key'),
            'timestamp' => time(),
        ];

        // Generate signature using tts_sign method
        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/order/202507/orders',
            null,
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        // Use Order Details API endpoint with specific order ID
        $url = 'https://open-api.tiktokglobalshop.com/order/202507/orders?' . http_build_query($signatureData['signed_query']);

        // Debug log
        Log::info('Order Details API Request', [
            'url' => $url,
            'order_id' => $orderId,
            'shop_cipher' => $shop->seller_cipher
        ]);

        // Make API request
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-tts-access-token' => $token->access_token,
        ])->get($url);

        if (!$response->successful()) {
            throw new \Exception('Yêu cầu API thất bại: ' . $response->body());
        }

        $data = $response->json();
        
        // Debug log response
        Log::info('Order Details API Response', [
            'order_id' => $orderId,
            'response_code' => $data['code'] ?? 'unknown',
            'orders_count' => count($data['data']['orders'] ?? []),
            'total_count' => $data['data']['total_count'] ?? 0
        ]);
        
        if (!$data || !isset($data['code']) || $data['code'] !== 0) {
            throw new \Exception('API trả về lỗi: ' . json_encode($data));
        }

        return $data;
    }

    /**
     * Get order statistics
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

            // Get orders data
            $orders = $this->fetchOrdersFromTikTok($shopId, $request);
            $ordersList = $orders['data']['orders'] ?? [];

            // Calculate statistics
            $stats = [
                'total_orders' => count($ordersList),
                'total_amount' => 0,
                'status_counts' => [],
                'currency_counts' => [],
            ];

            foreach ($ordersList as $order) {
                // Count by status
                $status = $order['status'] ?? 'UNKNOWN';
                $stats['status_counts'][$status] = ($stats['status_counts'][$status] ?? 0) + 1;

                // Sum total amount
                if (isset($order['payment']['total_amount'])) {
                    $stats['total_amount'] += (float) $order['payment']['total_amount'];
                }

                // Count by currency
                $currency = $order['payment']['currency'] ?? 'UNKNOWN';
                $stats['currency_counts'][$currency] = ($stats['currency_counts'][$currency] ?? 0) + 1;
            }

            return response()->json([
                'success' => true,
                'data' => $stats,
                'shop_id' => $shopId
            ]);

        } catch (\Exception $e) {
            Log::error('Order stats error', [
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
