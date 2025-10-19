<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TikTokShopTokenService;
use Illuminate\Http\Request;

class ShopApiController extends Controller
{
    protected $tokenService;

    public function __construct(TikTokShopTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * GET /api/shops
     */
    public function index(Request $request)
    {
        try {
            $shops = $this->tokenService->getAuthorizedShops();
            
            return response()->json([
                'success' => true,
                'data' => $shops
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Lỗi khi tải danh sách shop: ' . $e->getMessage()
            ], 500);
        }
    }
}
