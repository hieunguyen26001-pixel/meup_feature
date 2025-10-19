<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
     * GET /api/cancellations
     */
    public function index(Request $request)
    {
        try {
            $pageToken = $request->get('page_token');
            $pageSize = (int) $request->get('page_size', 20);
            $shopId = $request->get('shop_id');

            $shops = $this->tokenService->getAuthorizedShops();
            if (!$shopId && !empty($shops)) {
                $shopId = $shops[0]['shop_id'] ?? null;
            }

            // Return mock data for now
            return response()->json([
                'success' => true,
                'data' => $this->getMockCancellationsData($request)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Lỗi khi tải dữ liệu hủy đơn: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getMockCancellationsData(Request $request)
    {
        $pageSize = $request->get('page_size', 20);
        $pageToken = $request->get('page_token');
        
        // Mock cancellations data
        $mockCancellations = [
            [
                'cancel_id' => 'CANCEL_001',
                'cancel_type' => 'BUYER_CANCEL',
                'cancel_status' => 'CANCELLATION_REQUEST_PENDING',
                'cancel_reason' => 'Khách hàng không muốn mua nữa',
                'order_id' => 'ORDER_001',
                'product_name' => 'Áo thun nam',
                'quantity' => 1,
                'cancel_amount' => 150000,
                'created_at' => '2025-10-18 10:30:00',
                'updated_at' => '2025-10-18 10:30:00'
            ],
            [
                'cancel_id' => 'CANCEL_002',
                'cancel_type' => 'SELLER_CANCEL',
                'cancel_status' => 'CANCELLATION_APPROVED',
                'cancel_reason' => 'Hết hàng',
                'order_id' => 'ORDER_002',
                'product_name' => 'Quần jean nữ',
                'quantity' => 2,
                'cancel_amount' => 300000,
                'created_at' => '2025-10-18 09:15:00',
                'updated_at' => '2025-10-18 09:20:00'
            ],
            [
                'cancel_id' => 'CANCEL_003',
                'cancel_type' => 'BUYER_CANCEL',
                'cancel_status' => 'CANCELLATION_REJECTED',
                'cancel_reason' => 'Đổi ý',
                'order_id' => 'ORDER_003',
                'product_name' => 'Giày thể thao',
                'quantity' => 1,
                'cancel_amount' => 500000,
                'created_at' => '2025-10-18 08:45:00',
                'updated_at' => '2025-10-18 08:50:00'
            ]
        ];

        // Mock analytics data
        $mockAnalytics = [
            'total_cancellations' => count($mockCancellations),
            'buyer_cancellations' => 2,
            'seller_cancellations' => 1,
            'pending_cancellations' => 1,
            'approved_cancellations' => 1,
            'rejected_cancellations' => 1,
            'total_cancel_amount' => 950000
        ];

        return response()->json([
            'cancellations' => $mockCancellations,
            'analytics' => $mockAnalytics,
            'total_cancellations' => 3,
            'next_page_token' => null,
            'prev_page_token' => null,
            'message' => 'Mock data for testing - no valid token'
        ]);
    }
}