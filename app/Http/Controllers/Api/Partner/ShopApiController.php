<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;

class ShopApiController extends Controller
{
    /**
     * GET /api/shops
     */
    public function index(Request $request)
    {
        try {
            $shops = Shop::where('is_active', true)
                ->with('providerToken')
                ->get()
                ->map(function ($shop) {
                    return [
                        'shop_id' => $shop->shop_id,
                        'shop_name' => $shop->shop_name ?: "Shop {$shop->shop_id}",
                        'region' => $shop->region ?: 'VN',
                        'seller_cipher' => $shop->seller_cipher,
                        'is_active' => $shop->is_active,
                        'has_token' => $shop->providerToken ? true : false
                    ];
                });

            return response()->json([
                'success' => true,
                'shops' => $shops
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Lỗi khi tải danh sách shop: ' . $e->getMessage()
            ], 500);
        }
    }
}