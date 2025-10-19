<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Partner;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderApiController extends BasePartnerController
{
    /**
     * Get orders from TikTok Shop API
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $shopId = $this->getShopId($request);
            $pagination = $this->getPaginationParams($request);
            $dateRange = $this->processDateRange($request);

            $orders = $this->fetchOrdersFromTikTok($shopId, $request, $pagination, $dateRange);

            return $this->formatResponse([
                'orders' => $orders['orders'] ?? [],
                'total_count' => $orders['total_count'] ?? 0,
                'next_page_token' => $orders['next_page_token'] ?? null,
            ], $shopId);

        } catch (ValidationException $e) {
            return $this->validationError($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->validationError($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleApiError($e, 'tải dữ liệu orders');
        }
    }

    /**
     * Get order details by ID
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $this->validateRequiredParams($request, ['order_id']);

            $orderId = $request->get('order_id');
            $shopId = $this->getShopId($request);

            $orderDetails = $this->fetchOrderDetailsFromTikTok($shopId, $orderId);

            return $this->formatResponse($orderDetails, $shopId);

        } catch (ValidationException $e) {
            return $this->validationError($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->validationError($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleApiError($e, 'lấy chi tiết order');
        }
    }

    /**
     * Get order statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $shopId = $this->getShopId($request);
            $pagination = $this->getPaginationParams($request);
            $dateRange = $this->processDateRange($request);

            $orders = $this->fetchOrdersFromTikTok($shopId, $request, $pagination, $dateRange);
            $ordersList = $orders['orders'] ?? [];

            $stats = $this->calculateOrderStats($ordersList);

            return $this->formatResponse($stats, $shopId);

        } catch (\InvalidArgumentException $e) {
            return $this->validationError($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleApiError($e, 'tính toán thống kê orders');
        }
    }

    /**
     * Fetch orders from TikTok Shop API
     */
    protected function fetchOrdersFromTikTok(
        string $shopId,
        Request $request,
        array $pagination,
        array $dateRange
    ): array {
        $token = $this->getValidToken($shopId);
        $shop = $this->getShop($shopId);

        $params = $this->buildOrderParams($shop, $pagination, $request);
        $bodyData = $this->buildOrderBodyData($dateRange, $request);

        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/order/202309/orders/search',
            json_encode($bodyData),
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        $url = 'https://open-api.tiktokglobalshop.com/order/202309/orders/search?'.
               http_build_query($signatureData['signed_query']);

        return $this->makeTikTokApiRequest(
            $url,
            array_merge($params, ['access_token' => $token->access_token]),
            json_encode($bodyData),
            'POST'
        );
    }

    /**
     * Fetch order details from TikTok Shop API
     */
    protected function fetchOrderDetailsFromTikTok(string $shopId, string $orderId): array
    {
        $token = $this->getValidToken($shopId);
        $shop = $this->getShop($shopId);

        $params = [
            'shop_cipher' => $shop->seller_cipher,
            'order_id' => $orderId,
            'app_key' => config('services.tiktok_shop.client_key'),
            'timestamp' => time(),
        ];

        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/order/202309/orders',
            null,
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        $url = 'https://open-api.tiktokglobalshop.com/order/202309/orders?'.
               http_build_query($signatureData['signed_query']);

        return $this->makeTikTokApiRequest(
            $url,
            array_merge($params, ['access_token' => $token->access_token])
        );
    }

    /**
     * Build order parameters
     */
    protected function buildOrderParams(object $shop, array $pagination, Request $request): array
    {
        return [
            'shop_cipher' => $shop->seller_cipher,
            'page_size' => $pagination['page_size'],
            'app_key' => config('services.tiktok_shop.client_key'),
            'timestamp' => time(),
            'sort_field' => $request->get('sort_field', 'create_time'),
            'sort_order' => $request->get('sort_order', 'ASC'),
        ] + array_filter([
            'page_token' => $pagination['page_token'],
        ]);
    }

    /**
     * Build order body data
     */
    protected function buildOrderBodyData(array $dateRange, Request $request): array
    {
        $bodyData = array_merge([
            'create_time_ge' => $dateRange['create_time_ge'] ?? strtotime('-30 days'),
            'create_time_lt' => $dateRange['create_time_lt'] ?? time(),
            'update_time_ge' => $dateRange['update_time_ge'] ?? strtotime('-30 days'),
            'update_time_lt' => $dateRange['update_time_lt'] ?? time(),
            'locale' => $request->get('locale', 'en-US'),
        ], $this->getOptionalFilters($request));

        return $bodyData;
    }

    /**
     * Get optional filters from request
     */
    protected function getOptionalFilters(Request $request): array
    {
        $filters = [];

        $optionalFilters = [
            'order_ids',
            'buyer_user_ids',
            'order_status',
            'fulfillment_status',
            'shipping_providers',
            'warehouse_ids',
        ];

        foreach ($optionalFilters as $filter) {
            if ($request->has($filter) && ! empty($request->get($filter))) {
                $filters[$filter] = $request->get($filter);
            }
        }

        return $filters;
    }

    /**
     * Calculate order statistics
     */
    protected function calculateOrderStats(array $orders): array
    {
        $stats = [
            'total_orders' => count($orders),
            'total_gmv' => 0,
            'order_status_counts' => [],
            'fulfillment_status_counts' => [],
            'currency_counts' => [],
            'payment_method_counts' => [],
        ];

        foreach ($orders as $order) {
            // Count by order status
            $orderStatus = $order['order_status'] ?? 'UNKNOWN';
            $stats['order_status_counts'][$orderStatus] =
                ($stats['order_status_counts'][$orderStatus] ?? 0) + 1;

            // Count by fulfillment status
            $fulfillmentStatus = $order['fulfillment_status'] ?? 'UNKNOWN';
            $stats['fulfillment_status_counts'][$fulfillmentStatus] =
                ($stats['fulfillment_status_counts'][$fulfillmentStatus] ?? 0) + 1;

            // Sum total GMV
            if (isset($order['payment']['total_amount'])) {
                $stats['total_gmv'] += (float) $order['payment']['total_amount'];
            }

            // Count by currency
            $currency = $order['payment']['currency'] ?? 'UNKNOWN';
            $stats['currency_counts'][$currency] =
                ($stats['currency_counts'][$currency] ?? 0) + 1;

            // Count by payment method
            $paymentMethod = $order['payment_method_name'] ?? 'UNKNOWN';
            $stats['payment_method_counts'][$paymentMethod] =
                ($stats['payment_method_counts'][$paymentMethod] ?? 0) + 1;
        }

        return $stats;
    }
}
