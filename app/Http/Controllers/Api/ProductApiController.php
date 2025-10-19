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
        // 0) token
        $token = $this->tokenService->getValidToken($shopId);
        if (!$token) throw new \Exception('Không có token hợp lệ. Vui lòng ủy quyền lại.');

        // 1) region → host
        $region  = $token->metadata['seller_base_region'] ?? null;
        $apiBase = $region === 'US'
            ? rtrim((string) config('services.tiktok_shop.api_base_us', 'https://open-api.tiktokshop.com'), '/')
            : rtrim((string) config('services.tiktok_shop.api_base_global', 'https://open-api.tiktokglobalshop.com'), '/');

        // 2) path theo workspace (ảnh bạn gửi là 202309)
        $API_PATH = '/product/202309/products/search';
        $url      = $apiBase . $API_PATH;

        // 3) shop_cipher
        $shopInfo   = $this->tokenService->getShopInfo($token->access_token);
        $shopCipher = $this->extractShopCipher($shopInfo);
        if (!$shopCipher) throw new \Exception('Không tìm thấy shop_cipher');

        // 4) app_key
        $appKey = $token->app_key ?? (string) config('services.tiktok_shop.client_key');

        // 5) query thật (không có 'version', không param rỗng)
        $pageSize = (int) $request->get('page_size', 20);
        $qs = [
            'app_key'     => $appKey,
            'shop_cipher' => $shopCipher,
            'page_size'   => $pageSize,
        ];
        if ($request->filled('page_token')) {
            $qs['page_token'] = $request->get('page_token');
        }

        // 6) body: phải bọc "search": {...}
        $search = array_filter([
            'status'            => $request->get('status', 'ALL'),
            'category_version'  => $request->get('category_version', 'v1'),
            'seller_skus'       => $request->get('seller_skus'),
            'create_time_ge'    => $request->get('create_time_ge'),
            'create_time_le'    => $request->get('create_time_le'),
            'update_time_ge'    => $request->get('update_time_ge'),
            'update_time_le'    => $request->get('update_time_le'),
            'listing_platforms' => $request->get('listing_platforms'),
            'audit_status'      => $request->get('audit_status'),
            'sku_ids'           => $request->get('sku_ids'),
        ], fn($v) => $v !== null && $v !== '' && $v !== []);
        $bodyArr = ['search' => $search];
        $rawBody = json_encode($bodyArr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // 7) ký
        $appSecret = (string) config('services.tiktok_shop.client_secret');
        $sig = $this->tokenService->tts_sign($qs, $API_PATH, $rawBody, 'application/json', $appSecret);
        $signedQuery = $sig['signed_query']; // gồm cả timestamp & sign

        // 8) headers
        $headers = [
            'Content-Type'      => 'application/json',
            'x-tts-access-token' => $token->access_token,
        ];

        // 9) call
        $resp = Http::withHeaders($headers)
            ->withBody($rawBody, 'application/json')
            ->post($url . '?' . http_build_query($signedQuery));

        $data = $resp->json() ?? [];

        // 10) token invalid → refresh 1 lần
        if ($resp->status() === 401
            || (isset($data['code']) && (int)$data['code'] === 36009004
                && str_contains(strtolower($data['message'] ?? ''), 'access'))) {

            $fresh = $this->tokenService->ensureValidToken($shopId, true);
            $headers['X-Tt-Access-Token'] = $fresh->access_token;

            $resp  = Http::withHeaders($headers)
                ->withBody($rawBody, 'application/json')
                ->post($url . '?' . http_build_query($signedQuery));
            $data  = $resp->json() ?? [];
        }

        if (!$resp->successful() || (isset($data['code']) && (int)$data['code'] !== 0)) {
            throw new \Exception('Lỗi API TikTok Shop: ' . json_encode($data));
        }

        return $data;
    }

    /**
     * Extract shop cipher from shop info
     */
    private function extractShopCipher(array $shopInfo): ?string
    {
        if (isset($shopInfo['data']['shops'][0]['cipher'])) {
            return $shopInfo['data']['shops'][0]['cipher'];
        }
        return null;
    }

    /**
     * Mask sensitive data in logs
     */
    private function maskSensitive(array $data): array
    {
        $masked = $data;
        if (isset($masked['app_key'])) {
            $masked['app_key'] = substr($masked['app_key'], 0, 6) . '***';
        }
        if (isset($masked['shop_cipher'])) {
            $masked['shop_cipher'] = substr($masked['shop_cipher'], 0, 10) . '***';
        }
        return $masked;
    }

    /**
     * Get mock products for demo
     */
    private function getMockProducts(): array
    {
        return [
            'code' => 0,
            'message' => 'Success',
            'request_id' => 'mock_' . time(),
            'data' => [
                'products' => [
                    [
                        'id' => 'product_001',
                        'title' => 'Gen Nịt Bụng Cao Cấp - LAFIT',
                        'status' => 'ACTIVE',
                        'skus' => [
                            [
                                'id' => 'sku_001',
                                'seller_sku' => 'LAFIT-GEN-001',
                                'price' => [
                                    'tax_exclusive_price' => '299000'
                                ],
                                'inventory' => [
                                    [
                                        'quantity' => 100
                                    ]
                                ]
                            ]
                        ],
                        'images' => [
                            [
                                'url_list' => [
                                    'https://via.placeholder.com/300x300?text=Gen+Nịt+Bụng+1'
                                ]
                            ]
                        ],
                        'create_time' => 1694309208,
                        'update_time' => 1694319208
                    ],
                    [
                        'id' => 'product_002',
                        'title' => 'Áo Gen Nịt Bụng Thể Thao',
                        'status' => 'ACTIVE',
                        'skus' => [
                            [
                                'id' => 'sku_002',
                                'seller_sku' => 'LAFIT-GEN-002',
                                'price' => [
                                    'tax_exclusive_price' => '199000'
                                ],
                                'inventory' => [
                                    [
                                        'quantity' => 50
                                    ]
                                ]
                            ]
                        ],
                        'images' => [
                            [
                                'url_list' => [
                                    'https://via.placeholder.com/300x300?text=Áo+Gen+Nịt+Bụng'
                                ]
                            ]
                        ],
                        'create_time' => 1694309208,
                        'update_time' => 1694319208
                    ],
                    [
                        'id' => 'product_003',
                        'title' => 'Quần Gen Nịt Bụng Nữ',
                        'status' => 'ACTIVE',
                        'skus' => [
                            [
                                'id' => 'sku_003',
                                'seller_sku' => 'LAFIT-GEN-003',
                                'price' => [
                                    'tax_exclusive_price' => '249000'
                                ],
                                'inventory' => [
                                    [
                                        'quantity' => 75
                                    ]
                                ]
                            ]
                        ],
                        'images' => [
                            [
                                'url_list' => [
                                    'https://via.placeholder.com/300x300?text=Quần+Gen+Nịt+Bụng'
                                ]
                            ]
                        ],
                        'create_time' => 1694309208,
                        'update_time' => 1694319208
                    ]
                ],
                'total_count' => 3,
                'next_page_token' => null,
                'prev_page_token' => null
            ]
        ];
    }
}
