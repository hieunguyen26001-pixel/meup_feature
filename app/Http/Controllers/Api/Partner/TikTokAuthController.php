<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Services\TikTokShopTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TikTokAuthController extends Controller
{
    protected $tokenService;

    public function __construct(TikTokShopTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Generate TikTok Shop authorization URL
     */
    public function authorize(Request $request)
    {
        try {
            $appKey = config('services.tiktok_shop.client_key');
            $redirectUri = config('services.tiktok_shop.redirect_uri');
            $state = bin2hex(random_bytes(16));

            // Save state to database for CSRF protection
            \App\Models\OauthState::create([
                'provider' => 'SHOP',
                'state' => $state,
                'redirect' => $request->get('redirect', '/vue#/admin/products'),
                'expires_at' => now()->addMinutes(10)
            ]);

            $authUrl = "https://auth.tiktok-shops.com/oauth/authorize/seller?" . http_build_query([
                'app_key' => $appKey,
                'redirect_uri' => $redirectUri,
                'tts_state' => $state
            ]);

            return response()->json([
                'success' => true,
                'auth_url' => $authUrl,
                'state' => $state
            ]);

        } catch (\Exception $e) {
            Log::error('TikTok authorization URL generation failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Không thể tạo liên kết ủy quyền: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle TikTok Shop OAuth callback
     */
    public function callback(Request $request)
    {
        try {
            $code = $request->get('code');
            $state = $request->get('state');
            $error = $request->get('error');

            // Check for errors
            if ($error) {
                return response()->json([
                    'success' => false,
                    'error' => 'OAuth error: ' . $error
                ], 400);
            }

            // Validate state
            $oauthState = \App\Models\OauthState::where('state', $state)
                ->where('expires_at', '>', now())
                ->first();

            if (!$oauthState) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid or expired state parameter'
                ], 400);
            }

            // Exchange code for token
            $tokenData = $this->tokenService->exchangeCodeForToken($code);
            
            if (!$tokenData) {
                return response()->json([
                    'success' => false,
                    'error' => 'Token exchange failed'
                ], 500);
            }

            // Get shop info
            $shopInfo = $this->tokenService->getShopInfo($tokenData['access_token']);
            
            if (!$shopInfo || $shopInfo['code'] !== 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Get shop info failed: ' . json_encode($shopInfo)
                ], 500);
            }

            // Extract shop data from TikTok API response
            $shopData = $this->extractShopDataFromResponse($shopInfo, $tokenData);
            
            // Save shop info
            $shop = \App\Models\Shop::upsertFromTikTok($shopData, $tokenData);

            // Save token to ProviderToken table
            \App\Models\ProviderToken::updateOrCreate(
                [
                    'provider' => 'SHOP',
                    'subject_id' => $shopData['shop_id']
                ],
                [
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'],
                    'expires_at' => now()->addSeconds($tokenData['access_token_expire_in']),
                    'refresh_expires_at' => now()->addSeconds($tokenData['refresh_token_expire_in']),
                    'scopes' => $tokenData['granted_scopes'] ?? [],
                    'metadata' => [
                        'seller_name' => $tokenData['seller_name'] ?? null,
                        'seller_base_region' => $tokenData['seller_base_region'] ?? null,
                        'user_type' => $tokenData['user_type'] ?? null,
                        'open_id' => $tokenData['open_id'] ?? null,
                    ]
                ]
            );

            // Clean up state
            $oauthState->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ủy quyền thành công!',
                'shop' => [
                    'id' => $shop->id,
                    'shop_id' => $shop->shop_id,
                    'name' => $shop->shop_name,
                    'region' => $shop->region,
                    'seller_type' => $shop->seller_type
                ],
                'redirect' => $oauthState->redirect
            ]);

        } catch (\Exception $e) {
            Log::error('TikTok OAuth callback failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Xử lý callback thất bại: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract shop data from TikTok API response
     */
    private function extractShopDataFromResponse(array $shopInfo, array $tokenData): array
    {
        // TikTok Shop API response structure:
        // {
        //   "code": 0,
        //   "data": {
        //     "shops": [
        //       {
        //         "id": "7496239622529452872",
        //         "name": "Maomao beauty shop",
        //         "region": "GB",
        //         "seller_type": "CROSS_BORDER",
        //         "cipher": "GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3",
        //         "code": "CNGBCBA4LLU8"
        //       }
        //     ]
        //   }
        // }

        $shops = $shopInfo['data']['shops'] ?? [];
        if (empty($shops)) {
            throw new \Exception('No shops found in TikTok API response');
        }

        $shop = $shops[0]; // Get first shop

        return [
            'shop_id' => $shop['id'],
            'shop_name' => $shop['name'] ?? 'Unknown Shop',
            'region' => $shop['region'] ?? 'VN',
            'seller_type' => $shop['seller_type'] ?? null,
            'seller_cipher' => $shop['cipher'] ?? null,
            'metadata' => [
                'shop_code' => $shop['code'] ?? null,
                'seller_base_region' => $tokenData['seller_base_region'] ?? null,
                'seller_name' => $tokenData['seller_name'] ?? null,
                'user_type' => $tokenData['user_type'] ?? null,
            ]
        ];
    }
}
