<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use App\Services\TikTokShopTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopAnalyticsController extends Controller
{
    use ApiResponseTrait;

    protected $tokenService;

    public function __construct(TikTokShopTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * 1. Phân tích tổng quan shop
     */
    public function shopOverview(Request $request)
    {
        try {
            $shopId = $request->get('shop_id');
            if (!$shopId) {
                return $this->errorResponse('Shop ID là bắt buộc', 400);
            }

            // TODO: Implement shop overview analytics
            // - Tổng doanh thu
            // - Số đơn hàng
            // - Tỷ lệ chuyển đổi
            // - Đánh giá trung bình
            // - Top sản phẩm bán chạy

            return $this->successResponse([
                'message' => 'API phân tích tổng quan shop - Đang phát triển',
                'shop_id' => $shopId,
                'features' => [
                    'Tổng doanh thu',
                    'Số đơn hàng',
                    'Tỷ lệ chuyển đổi',
                    'Đánh giá trung bình',
                    'Top sản phẩm bán chạy'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Shop overview analytics error', [
                'error' => $e->getMessage(),
                'shop_id' => $request->get('shop_id')
            ]);

            return $this->errorResponse('Lỗi khi phân tích tổng quan shop: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Shop Performance Analytics - Lấy dữ liệu hiệu suất shop từ TikTok API
     */
    public function shopPerformance(Request $request)
    {
        try {
            $shopId = $request->get('shop_id');
            if (!$shopId) {
                return $this->errorResponse('Shop ID là bắt buộc', 400);
            }

            // Get shop performance data from TikTok API
            $performanceData = $this->fetchShopPerformanceFromTikTok($shopId, $request);

            return $this->successResponse([
                'message' => 'Dữ liệu hiệu suất shop',
                'shop_id' => $shopId,
                'data' => $performanceData,
                'source' => 'tiktok_api'
            ]);

        } catch (\Exception $e) {
            Log::error('Shop performance analytics error', [
                'error' => $e->getMessage(),
                'shop_id' => $request->get('shop_id')
            ]);

            return $this->errorResponse('Lỗi khi lấy dữ liệu hiệu suất shop: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 2. Phân tích sản phẩm
     */
    public function productAnalytics(Request $request)
    {
        try {
            $shopId = $request->get('shop_id');
            if (!$shopId) {
                return $this->errorResponse('Shop ID là bắt buộc', 400);
            }

            // TODO: Implement product analytics
            // - Phân tích hiệu suất sản phẩm
            // - Top sản phẩm bán chạy
            // - Sản phẩm có tỷ lệ hoàn trả cao
            // - Phân tích giá cả
            // - Phân tích danh mục

            return $this->successResponse([
                'message' => 'API phân tích sản phẩm - Đang phát triển',
                'shop_id' => $shopId,
                'features' => [
                    'Hiệu suất sản phẩm',
                    'Top sản phẩm bán chạy',
                    'Sản phẩm có tỷ lệ hoàn trả cao',
                    'Phân tích giá cả',
                    'Phân tích danh mục'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Product analytics error', [
                'error' => $e->getMessage(),
                'shop_id' => $request->get('shop_id')
            ]);

            return $this->errorResponse('Lỗi khi phân tích sản phẩm: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Product Performance Analytics - Lấy dữ liệu hiệu suất sản phẩm từ TikTok API
     */
    public function productPerformance(Request $request)
    {
        try {
            $shopId = $request->get('shop_id');
            $productId = $request->get('product_id');
            
            if (!$shopId) {
                return $this->errorResponse('Shop ID là bắt buộc', 400);
            }
            
            if (!$productId) {
                return $this->errorResponse('Product ID là bắt buộc', 400);
            }

            // Get product performance data from TikTok API
            $performanceData = $this->fetchProductPerformanceFromTikTok($shopId, $productId, $request);

            return $this->successResponse([
                'message' => 'Dữ liệu hiệu suất sản phẩm',
                'shop_id' => $shopId,
                'product_id' => $productId,
                'data' => $performanceData,
                'source' => 'tiktok_api'
            ]);

        } catch (\Exception $e) {
            Log::error('Product performance analytics error', [
                'error' => $e->getMessage(),
                'shop_id' => $request->get('shop_id'),
                'product_id' => $request->get('product_id')
            ]);

            return $this->errorResponse('Lỗi khi lấy dữ liệu hiệu suất sản phẩm: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Products Performance List - Lấy danh sách hiệu suất tất cả sản phẩm từ TikTok API
     */
    public function productsPerformanceList(Request $request)
    {
        try {
            $shopId = $request->get('shop_id');
            
            if (!$shopId) {
                return $this->errorResponse('Shop ID là bắt buộc', 400);
            }

            // Get products performance list data from TikTok API
            $performanceData = $this->fetchProductsPerformanceListFromTikTok($shopId, $request);

            return $this->successResponse([
                'message' => 'Danh sách hiệu suất sản phẩm',
                'shop_id' => $shopId,
                'data' => $performanceData,
                'source' => 'tiktok_api'
            ]);

        } catch (\Exception $e) {
            Log::error('Products performance list analytics error', [
                'error' => $e->getMessage(),
                'shop_id' => $request->get('shop_id')
            ]);

            return $this->errorResponse('Lỗi khi lấy danh sách hiệu suất sản phẩm: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 3. Phân tích video
     */
    public function videoAnalytics(Request $request)
    {
        try {
            $shopId = $request->get('shop_id');
            if (!$shopId) {
                return $this->errorResponse('Shop ID là bắt buộc', 400);
            }

            // Get video performance data from TikTok API
            $videoData = $this->fetchVideoPerformanceFromTikTok($shopId, $request);

            return $this->successResponse([
                'message' => 'Dữ liệu phân tích video',
                'shop_id' => $shopId,
                'data' => $videoData,
                'source' => 'tiktok_api'
            ]);

        } catch (\Exception $e) {
            Log::error('Video analytics error', [
                'error' => $e->getMessage(),
                'shop_id' => $request->get('shop_id')
            ]);

            return $this->errorResponse('Lỗi khi phân tích video: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Fetch video performance data from TikTok API
     */
    private function fetchVideoPerformanceFromTikTok(string $shopId, Request $request): array
    {
        // Get valid token
        $token = $this->tokenService->getValidToken($shopId);
        if (!$token) {
            throw new \Exception('Không có token hợp lệ. Vui lòng ủy quyền lại.');
        }

        // Get shop info
        $shop = \App\Models\Shop::where('shop_id', $shopId)->first();
        if (!$shop) {
            throw new \Exception('Không tìm thấy thông tin shop');
        }

        // Build parameters for Video Performance API
        // Use recent dates (last 30 days) as TikTok API may not have data for old dates
        $startDate = $request->get('start_date_ge', date('Y-m-d', strtotime('-30 days')));
        $endDate = $request->get('end_date_lt', date('Y-m-d', strtotime('-1 day')));
        
        // Ensure dates are in correct format (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $startDate = date('Y-m-d', strtotime($startDate));
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $endDate = date('Y-m-d', strtotime($endDate));
        }
        
        // Ensure end date is not in the future
        if (strtotime($endDate) > time()) {
            $endDate = date('Y-m-d', strtotime('-1 day'));
        }
        
        $params = [
            'shop_cipher' => $shop->seller_cipher,
            'app_key' => config('services.tiktok_shop.client_key'),
            'timestamp' => time(),
            'account_type' => 'ALL',
            'page_size' => $request->get('page_size', 10),
            'currency' => $request->get('currency', 'USD'),
            'sort_order' => $request->get('sort_order', 'DESC'),
            'sort_field' => $request->get('sort_field', 'gmv'),
            'start_date_ge' => $startDate,
            'end_date_lt' => $endDate,
        ];

        // Add page_token if provided
        if ($request->has('page_token')) {
            $params['page_token'] = $request->get('page_token');
        }

        // Generate signature using tts_sign method
        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/analytics/202409/shop_videos/performance',
            null,
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        // Build URL
        $url = 'https://open-api.tiktokglobalshop.com/analytics/202409/shop_videos/performance?' . http_build_query($signatureData['signed_query']);


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
     * Fetch shop performance data from TikTok API
     */
    private function fetchShopPerformanceFromTikTok(string $shopId, Request $request): array
    {
        // Get valid token
        $token = $this->tokenService->getValidToken($shopId);
        if (!$token) {
            throw new \Exception('Không có token hợp lệ. Vui lòng ủy quyền lại.');
        }

        // Get shop info
        $shop = \App\Models\Shop::where('shop_id', $shopId)->first();
        if (!$shop) {
            throw new \Exception('Không tìm thấy thông tin shop');
        }

        // Build parameters for Shop Performance API
        // Use more recent dates as TikTok API may not have data for old dates
        $startDate = $request->get('start_date_ge', date('Y-m-d', strtotime('-7 days')));
        $endDate = $request->get('end_date_lt', date('Y-m-d', strtotime('-1 day')));
        
        // Ensure dates are in correct format (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $startDate = date('Y-m-d', strtotime($startDate));
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $endDate = date('Y-m-d', strtotime($endDate));
        }
        
        // Ensure end date is not in the future and not too recent
        if (strtotime($endDate) > time()) {
            $endDate = date('Y-m-d', strtotime('-1 day'));
        }
        
        // Ensure start date is not too far in the past (TikTok API limitation)
        if (strtotime($startDate) < strtotime('-90 days')) {
            $startDate = date('Y-m-d', strtotime('-7 days'));
        }
        
        $params = [
            'shop_cipher' => $shop->seller_cipher,
            'app_key' => config('services.tiktok_shop.client_key'),
            'timestamp' => time(),
            'start_date_ge' => $startDate,
            'end_date_lt' => $endDate,
            'granularity' => $request->get('granularity', 'ALL'),
            'currency' => $request->get('currency', 'LOCAL'),
            'with_comparison' => $request->get('with_comparison', 'true'),
        ];

        // Generate signature using tts_sign method
        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/analytics/202405/shop/performance',
            null,
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        // Build URL
        $url = 'https://open-api.tiktokglobalshop.com/analytics/202405/shop/performance?' . http_build_query($signatureData['signed_query']);

        // Make API request
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-tts-access-token' => $token->access_token,
        ])->get($url);

        if (!$response->successful()) {
            Log::error('TikTok Shop Performance API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $url,
                'params' => $params
            ]);
            
            $errorData = $response->json();
            if (isset($errorData['code']) && $errorData['code'] == 106011) {
                throw new \Exception('Shop cipher không hợp lệ. Vui lòng ủy quyền lại shop để lấy shop_cipher thực từ TikTok Shop.');
            }
            
            throw new \Exception('Yêu cầu API thất bại: ' . $response->body());
        }

        $data = $response->json();
        
        if (!$data || !isset($data['code']) || $data['code'] !== 0) {
            throw new \Exception('API trả về lỗi: ' . json_encode($data));
        }

        return $data;
    }

    /**
     * Fetch product performance data from TikTok API
     */
    private function fetchProductPerformanceFromTikTok(string $shopId, string $productId, Request $request): array
    {
        // Get valid token
        $token = $this->tokenService->getValidToken($shopId);
        if (!$token) {
            throw new \Exception('Không có token hợp lệ. Vui lòng ủy quyền lại.');
        }

        // Get shop info
        $shop = \App\Models\Shop::where('shop_id', $shopId)->first();
        if (!$shop) {
            throw new \Exception('Không tìm thấy thông tin shop');
        }

        // Build parameters for Product Performance API
        // Use more recent dates as TikTok API may not have data for old dates
        $startDate = $request->get('start_date_ge', date('Y-m-d', strtotime('-7 days')));
        $endDate = $request->get('end_date_lt', date('Y-m-d', strtotime('-1 day')));
        
        // Ensure dates are in correct format (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $startDate = date('Y-m-d', strtotime($startDate));
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $endDate = date('Y-m-d', strtotime($endDate));
        }
        
        // Ensure end date is not in the future and not too recent
        if (strtotime($endDate) > time()) {
            $endDate = date('Y-m-d', strtotime('-1 day'));
        }
        
        // Ensure start date is not too far in the past (TikTok API limitation)
        if (strtotime($startDate) < strtotime('-90 days')) {
            $startDate = date('Y-m-d', strtotime('-7 days'));
        }
        
        $params = [
            'shop_cipher' => $shop->seller_cipher,
            'app_key' => config('services.tiktok_shop.client_key'),
            'timestamp' => time(),
            'start_date_ge' => $startDate,
            'end_date_lt' => $endDate,
            'granularity' => $request->get('granularity', 'ALL'),
            'currency' => $request->get('currency', 'LOCAL'),
            'with_comparison' => $request->get('with_comparison', 'true'),
        ];

        // Generate signature using tts_sign method
        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/analytics/202405/shop_products/' . $productId . '/performance',
            null,
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        // Build URL
        $url = 'https://open-api.tiktokglobalshop.com/analytics/202405/shop_products/' . $productId . '/performance?' . http_build_query($signatureData['signed_query']);

        // Make API request
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-tts-access-token' => $token->access_token,
        ])->get($url);

        if (!$response->successful()) {
            Log::error('TikTok Product Performance API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $url,
                'params' => $params,
                'product_id' => $productId
            ]);
            
            $errorData = $response->json();
            if (isset($errorData['code']) && $errorData['code'] == 106011) {
                throw new \Exception('Shop cipher không hợp lệ. Vui lòng ủy quyền lại shop để lấy shop_cipher thực từ TikTok Shop.');
            }
            
            throw new \Exception('Yêu cầu API thất bại: ' . $response->body());
        }

        $data = $response->json();
        
        if (!$data || !isset($data['code']) || $data['code'] !== 0) {
            throw new \Exception('API trả về lỗi: ' . json_encode($data));
        }

        return $data;
    }

    /**
     * Fetch products performance list data from TikTok API
     */
    private function fetchProductsPerformanceListFromTikTok(string $shopId, Request $request): array
    {
        // Get valid token
        $token = $this->tokenService->getValidToken($shopId);
        if (!$token) {
            throw new \Exception('Không có token hợp lệ. Vui lòng ủy quyền lại.');
        }

        // Get shop info
        $shop = \App\Models\Shop::where('shop_id', $shopId)->first();
        if (!$shop) {
            throw new \Exception('Không tìm thấy thông tin shop');
        }

        // Build parameters for Products Performance List API
        // Use more recent dates as TikTok API may not have data for old dates
        $startDate = $request->get('start_date_ge', date('Y-m-d', strtotime('-7 days')));
        $endDate = $request->get('end_date_lt', date('Y-m-d', strtotime('-1 day')));
        
        // Ensure dates are in correct format (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $startDate = date('Y-m-d', strtotime($startDate));
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $endDate = date('Y-m-d', strtotime($endDate));
        }
        
        // Ensure end date is not in the future and not too recent
        if (strtotime($endDate) > time()) {
            $endDate = date('Y-m-d', strtotime('-1 day'));
        }
        
        // Ensure start date is not too far in the past (TikTok API limitation)
        if (strtotime($startDate) < strtotime('-90 days')) {
            $startDate = date('Y-m-d', strtotime('-7 days'));
        }
        
        $params = [
            'shop_cipher' => $shop->seller_cipher,
            'app_key' => config('services.tiktok_shop.client_key'),
            'timestamp' => time(),
            'start_date_ge' => $startDate,
            'end_date_lt' => $endDate,
            'page_size' => $request->get('page_size', 10),
            'page_token' => $request->get('page_token'),
            'sort_field' => $request->get('sort_field', 'gmv'),
            'sort_order' => $request->get('sort_order', 'DESC'),
            'currency' => $request->get('currency', 'LOCAL'),
        ];

        // Remove empty page_token
        if (empty($params['page_token'])) {
            unset($params['page_token']);
        }

        // Generate signature using tts_sign method
        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/analytics/202405/shop_products/performance',
            null,
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        // Build URL
        $url = 'https://open-api.tiktokglobalshop.com/analytics/202405/shop_products/performance?' . http_build_query($signatureData['signed_query']);

        // Make API request
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-tts-access-token' => $token->access_token,
        ])->get($url);

        if (!$response->successful()) {
            Log::error('TikTok Products Performance List API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $url,
                'params' => $params
            ]);
            
            $errorData = $response->json();
            if (isset($errorData['code']) && $errorData['code'] == 106011) {
                throw new \Exception('Shop cipher không hợp lệ. Vui lòng ủy quyền lại shop để lấy shop_cipher thực từ TikTok Shop.');
            }
            
            throw new \Exception('Yêu cầu API thất bại: ' . $response->body());
        }

        $data = $response->json();
        
        if (!$data || !isset($data['code']) || $data['code'] !== 0) {
            throw new \Exception('API trả về lỗi: ' . json_encode($data));
        }

        return $data;
    }

    /**
     * 4. Phân tích live
     */
    public function liveAnalytics(Request $request)
    {
        try {
            $shopId = $request->get('shop_id');
            if (!$shopId) {
                return $this->errorResponse('Shop ID là bắt buộc', 400);
            }

            // TODO: Implement live analytics
            // - Phân tích hiệu suất live stream
            // - Live có lượt xem cao nhất
            // - Live có doanh thu cao nhất
            // - Phân tích thời gian live
            // - Phân tích tương tác live

            return $this->successResponse([
                'message' => 'API phân tích live - Đang phát triển',
                'shop_id' => $shopId,
                'features' => [
                    'Hiệu suất live stream',
                    'Live có lượt xem cao nhất',
                    'Live có doanh thu cao nhất',
                    'Phân tích thời gian live',
                    'Phân tích tương tác live'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Live analytics error', [
                'error' => $e->getMessage(),
                'shop_id' => $request->get('shop_id')
            ]);

            return $this->errorResponse('Lỗi khi phân tích live: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy danh sách shop để phân tích
     */
    public function shops(Request $request)
    {
        try {
            // TODO: Implement shop list for analytics
            // - Lấy danh sách shop đã ủy quyền
            // - Thông tin cơ bản của shop
            // - Trạng thái hoạt động

            return $this->successResponse([
                'message' => 'API danh sách shop - Đang phát triển',
                'features' => [
                    'Danh sách shop đã ủy quyền',
                    'Thông tin cơ bản của shop',
                    'Trạng thái hoạt động'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Shop list error', [
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Lỗi khi lấy danh sách shop: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy thống kê tổng quan
     */
    public function stats(Request $request)
    {
        try {
            $shopId = $request->get('shop_id');
            if (!$shopId) {
                return $this->errorResponse('Shop ID là bắt buộc', 400);
            }

            // TODO: Implement general stats
            // - Thống kê tổng quan
            // - Biểu đồ dữ liệu
            // - So sánh theo thời gian

            return $this->successResponse([
                'message' => 'API thống kê tổng quan - Đang phát triển',
                'shop_id' => $shopId,
                'features' => [
                    'Thống kê tổng quan',
                    'Biểu đồ dữ liệu',
                    'So sánh theo thời gian'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Stats error', [
                'error' => $e->getMessage(),
                'shop_id' => $request->get('shop_id')
            ]);

            return $this->errorResponse('Lỗi khi lấy thống kê: ' . $e->getMessage(), 500);
        }
    }
}
