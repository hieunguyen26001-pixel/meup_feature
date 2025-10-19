<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TikTokShopTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductApiController extends Controller
{
    protected $tokenService;

    public function __construct(TikTokShopTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Get products from TikTok Shop API
     */
    public function index(Request $request)
    {
        try {
            // Get shop ID from request or use first available shop
            $shopId = $request->get('shop_id');
            if (!$shopId) {
                $shops = $this->tokenService->getAuthorizedShops();
                if (empty($shops)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Không có shop nào được ủy quyền'
                    ], 400);
                }
                $shopId = $shops[0]['shop_id'];
            }


            // Fetch products from TikTok API
            try {
                $products = $this->fetchProducts($shopId, $request);

                Log::info('Products API success', [
                    'shop_id' => $shopId,
                    'products_count' => count($products['data']['products'] ?? [])
                ]);
            } catch (\Exception $e) {
                Log::error('Products API failed', [
                    'shop_id' => $shopId,
                    'error' => $e->getMessage()
                ]);

                // Return error with more details for debugging
                return response()->json([
                    'success' => false,
                    'error' => 'Lỗi khi tải sản phẩm từ TikTok Shop: ' . $e->getMessage(),
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
                'data' => $products,
                'shop_id' => $shopId
            ]);

        } catch (\Exception $e) {
            Log::error('Product API error', [
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
     * Fetch products from TikTok Shop API
     * @throws \Exception
     */
    public function fetchProducts(string $shopId, Request $request): array
    {
        $token = $this->tokenService->getValidToken($shopId);
        if (!$token) {
            throw new \Exception('Không có token hợp lệ. Vui lòng ủy quyền lại.');
        }

        $shop = \App\Models\Shop::where('shop_id', $shopId)->first();
        if (!$shop) {
            throw new \Exception('Không tìm thấy thông tin shop');
        }

        // Parse parameters
        $pageSize = $request->get('page_size', 20);
        $pageToken = $request->get('page_token', '');

        $params = [
            'shop_cipher' => $shop->seller_cipher,
            'app_key' => config('services.tiktok_shop.client_key'),
            'timestamp' => time(),
            'page_size' => $pageSize,
            'page_token' => $pageToken,
        ];

        $requestBody = [
            'status' => 'ALL',
            'category_version' => 'v1',
            'listing_platforms' => ['TIKTOK_SHOP'],
        ];

        $body = json_encode($requestBody);

        // Use the existing tts_sign method
        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/product/202502/products/search',
            $body,
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        $url = 'https://open-api.tiktokglobalshop.com/product/202502/products/search?' . http_build_query($signatureData['signed_query']);


        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-tts-access-token' => $token->access_token,
        ])->withBody($body, 'application/json')
        ->post($url);

        if (!$response->successful()) {
            throw new \Exception('Yêu cầu API sản phẩm thất bại: ' . $response->body());
        }

        $data = $response->json();


        if (!$data || !isset($data['code']) || $data['code'] !== 0) {
            throw new \Exception('API sản phẩm trả về lỗi: ' . json_encode($data));
        }

        return $data;
    }
}
