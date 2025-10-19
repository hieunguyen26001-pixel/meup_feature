<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Partner;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReturnApiController extends BasePartnerController
{
    /**
     * Get returns/refunds from TikTok Shop API
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $shopId = $this->getShopId($request);
            $pagination = $this->getPaginationParams($request);
            $dateRange = $this->processDateRange($request);

            $returns = $this->fetchReturnsFromTikTok($shopId, $request, $pagination, $dateRange);

            return $this->formatResponse([
                'returns' => $returns['returns'] ?? [],
                'total_count' => $returns['total_count'] ?? 0,
                'next_page_token' => $returns['next_page_token'] ?? null,
            ], $shopId);

        } catch (ValidationException $e) {
            return $this->validationError($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->validationError($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleApiError($e, 'tải dữ liệu returns');
        }
    }

    /**
     * Get return details by ID
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $this->validateRequiredParams($request, ['return_id']);

            $returnId = $request->get('return_id');
            $shopId = $this->getShopId($request);

            $returnDetails = $this->fetchReturnDetailsFromTikTok($shopId, $returnId);

            return $this->formatResponse($returnDetails, $shopId);

        } catch (ValidationException $e) {
            return $this->validationError($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->validationError($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleApiError($e, 'lấy chi tiết return');
        }
    }

    /**
     * Get return statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $shopId = $this->getShopId($request);
            $pagination = $this->getPaginationParams($request);
            $dateRange = $this->processDateRange($request);

            $returns = $this->fetchReturnsFromTikTok($shopId, $request, $pagination, $dateRange);
            $returnsList = $returns['returns'] ?? [];

            $stats = $this->calculateReturnStats($returnsList);

            return $this->formatResponse($stats, $shopId);

        } catch (\InvalidArgumentException $e) {
            return $this->validationError($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleApiError($e, 'tính toán thống kê returns');
        }
    }

    /**
     * Fetch returns from TikTok Shop API
     */
    protected function fetchReturnsFromTikTok(
        string $shopId,
        Request $request,
        array $pagination,
        array $dateRange
    ): array {
        $token = $this->getValidToken($shopId);
        $shop = $this->getShop($shopId);

        $params = $this->buildReturnParams($shop, $pagination, $request);
        $bodyData = $this->buildReturnBodyData($dateRange, $request);

        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/return_refund/202309/returns/search',
            json_encode($bodyData),
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        $url = 'https://open-api.tiktokglobalshop.com/return_refund/202309/returns/search?'.
               http_build_query($signatureData['signed_query']);

        return $this->makeTikTokApiRequest(
            $url,
            array_merge($params, ['access_token' => $token->access_token]),
            json_encode($bodyData),
            'POST'
        );
    }

    /**
     * Fetch return details from TikTok Shop API
     */
    protected function fetchReturnDetailsFromTikTok(string $shopId, string $returnId): array
    {
        $token = $this->getValidToken($shopId);
        $shop = $this->getShop($shopId);

        $params = [
            'shop_cipher' => $shop->seller_cipher,
            'return_id' => $returnId,
            'app_key' => config('services.tiktok_shop.client_key'),
            'timestamp' => time(),
        ];

        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/return_refund/202309/returns',
            null,
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        $url = 'https://open-api.tiktokglobalshop.com/return_refund/202309/returns?'.
               http_build_query($signatureData['signed_query']);

        return $this->makeTikTokApiRequest(
            $url,
            array_merge($params, ['access_token' => $token->access_token])
        );
    }

    /**
     * Build return parameters
     */
    protected function buildReturnParams(object $shop, array $pagination, Request $request): array
    {
        return [
            'shop_cipher' => $shop->seller_cipher,
            'page_size' => $pagination['page_size'],
            'app_key' => config('services.tiktok_shop.client_key'),
            'timestamp' => time(),
            'sort_field' => $request->get('sort_field', 'update_time'),
            'sort_order' => $request->get('sort_order', 'ASC'),
        ] + array_filter([
            'page_token' => $pagination['page_token'],
        ]);
    }

    /**
     * Build return body data
     */
    protected function buildReturnBodyData(array $dateRange, Request $request): array
    {
        $bodyData = array_merge([
            'create_time_ge' => $dateRange['create_time_ge'] ?? strtotime('-30 days'),
            'create_time_lt' => $dateRange['create_time_lt'] ?? time(),
            'update_time_ge' => $dateRange['update_time_ge'] ?? strtotime('-30 days'),
            'update_time_lt' => $dateRange['update_time_lt'] ?? time(),
            'locale' => $request->get('locale', 'en-US'),
        ], $this->getReturnFilters($request));

        return $bodyData;
    }

    /**
     * Get return filters from request
     */
    protected function getReturnFilters(Request $request): array
    {
        $filters = [];

        $optionalFilters = [
            'return_ids',
            'order_ids',
            'buyer_user_ids',
            'return_types',
            'return_status',
        ];

        foreach ($optionalFilters as $filter) {
            if ($request->has($filter) && ! empty($request->get($filter))) {
                $filters[$filter] = $request->get($filter);
            }
        }

        return $filters;
    }

    /**
     * Calculate return statistics
     */
    protected function calculateReturnStats(array $returns): array
    {
        $stats = [
            'total_returns' => count($returns),
            'total_refund_amount' => 0,
            'return_type_counts' => [],
            'return_status_counts' => [],
            'currency_counts' => [],
        ];

        foreach ($returns as $return) {
            // Count by return type
            $returnType = $return['return_type'] ?? 'UNKNOWN';
            $stats['return_type_counts'][$returnType] =
                ($stats['return_type_counts'][$returnType] ?? 0) + 1;

            // Count by return status
            $returnStatus = $return['return_status'] ?? 'UNKNOWN';
            $stats['return_status_counts'][$returnStatus] =
                ($stats['return_status_counts'][$returnStatus] ?? 0) + 1;

            // Sum total refund amount
            if (isset($return['refund_amount']['refund_total'])) {
                $stats['total_refund_amount'] += (float) $return['refund_amount']['refund_total'];
            }

            // Count by currency
            $currency = $return['refund_amount']['currency'] ?? 'UNKNOWN';
            $stats['currency_counts'][$currency] =
                ($stats['currency_counts'][$currency] ?? 0) + 1;
        }

        return $stats;
    }
}
