<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Partner;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CancellationApiController extends BasePartnerController
{
    /**
     * Get cancellations from TikTok Shop API
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $shopId = $this->getShopId($request);
            $pagination = $this->getPaginationParams($request);
            $dateRange = $this->processDateRange($request);

            $cancellations = $this->fetchCancellationsFromTikTok($shopId, $request, $pagination, $dateRange);

            return $this->formatResponse([
                'cancellations' => $cancellations['cancellations'] ?? [],
                'total_count' => $cancellations['total_count'] ?? 0,
                'next_page_token' => $cancellations['next_page_token'] ?? null,
            ], $shopId);

        } catch (ValidationException $e) {
            return $this->validationError($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->validationError($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleApiError($e, 'tải dữ liệu cancellations');
        }
    }

    /**
     * Get cancellation details by ID
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $this->validateRequiredParams($request, ['cancel_id']);

            $cancelId = $request->get('cancel_id');
            $shopId = $this->getShopId($request);

            $cancellationDetails = $this->fetchCancellationDetailsFromTikTok($shopId, $cancelId);

            return $this->formatResponse($cancellationDetails, $shopId);

        } catch (ValidationException $e) {
            return $this->validationError($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->validationError($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleApiError($e, 'lấy chi tiết cancellation');
        }
    }

    /**
     * Get cancellation statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $shopId = $this->getShopId($request);
            $pagination = $this->getPaginationParams($request);
            $dateRange = $this->processDateRange($request);

            $cancellations = $this->fetchCancellationsFromTikTok($shopId, $request, $pagination, $dateRange);
            $cancellationsList = $cancellations['cancellations'] ?? [];

            $stats = $this->calculateCancellationStats($cancellationsList);

            return $this->formatResponse($stats, $shopId);

        } catch (\InvalidArgumentException $e) {
            return $this->validationError($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleApiError($e, 'tính toán thống kê cancellations');
        }
    }

    /**
     * Fetch cancellations from TikTok Shop API
     */
    protected function fetchCancellationsFromTikTok(
        string $shopId,
        Request $request,
        array $pagination,
        array $dateRange
    ): array {
        $token = $this->getValidToken($shopId);
        $shop = $this->getShop($shopId);

        $params = $this->buildCancellationParams($shop, $pagination, $request);
        $bodyData = $this->buildCancellationBodyData($dateRange, $request);

        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/return_refund/202309/cancellations/search',
            json_encode($bodyData),
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        $url = 'https://open-api.tiktokglobalshop.com/return_refund/202309/cancellations/search?'.
               http_build_query($signatureData['signed_query']);

        return $this->makeTikTokApiRequest(
            $url,
            array_merge($params, ['access_token' => $token->getAttribute('access_token')]),
            json_encode($bodyData),
            'POST'
        );
    }

    /**
     * Fetch cancellation details from TikTok Shop API
     */
    protected function fetchCancellationDetailsFromTikTok(string $shopId, string $cancelId): array
    {
        $token = $this->getValidToken($shopId);
        $shop = $this->getShop($shopId);

        $params = [
            'shop_cipher' => $shop->seller_cipher,
            'cancel_id' => $cancelId,
            'app_key' => config('services.tiktok_shop.client_key'),
            'timestamp' => time(),
        ];

        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/return_refund/202309/cancellations',
            null,
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        $url = 'https://open-api.tiktokglobalshop.com/return_refund/202309/cancellations?'.
               http_build_query($signatureData['signed_query']);

        return $this->makeTikTokApiRequest(
            $url,
            array_merge($params, ['access_token' => $token->getAttribute('access_token')])
        );
    }

    /**
     * Build cancellation parameters
     */
    protected function buildCancellationParams(object $shop, array $pagination, Request $request): array
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
     * Build cancellation body data
     */
    protected function buildCancellationBodyData(array $dateRange, Request $request): array
    {
        $bodyData = array_merge([
            'create_time_ge' => $dateRange['create_time_ge'] ?? strtotime('-30 days'),
            'create_time_lt' => $dateRange['create_time_lt'] ?? time(),
            'update_time_ge' => $dateRange['update_time_ge'] ?? strtotime('-30 days'),
            'update_time_lt' => $dateRange['update_time_lt'] ?? time(),
            'locale' => $request->get('locale', 'en-US'),
        ], $this->getCancellationFilters($request));

        return $bodyData;
    }

    /**
     * Get cancellation filters from request
     */
    protected function getCancellationFilters(Request $request): array
    {
        $filters = [];

        $optionalFilters = [
            'cancel_ids',
            'order_ids',
            'buyer_user_ids',
            'cancel_types',
            'cancel_status',
        ];

        foreach ($optionalFilters as $filter) {
            if ($request->has($filter) && ! empty($request->get($filter))) {
                $filters[$filter] = $request->get($filter);
            }
        }

        return $filters;
    }

    /**
     * Calculate cancellation statistics
     */
    protected function calculateCancellationStats(array $cancellations): array
    {
        $stats = [
            'total_cancellations' => count($cancellations),
            'total_refund_amount' => 0,
            'cancel_type_counts' => [],
            'cancel_status_counts' => [],
            'currency_counts' => [],
        ];

        foreach ($cancellations as $cancellation) {
            // Count by cancel type
            $cancelType = $cancellation['cancel_type'] ?? 'UNKNOWN';
            $stats['cancel_type_counts'][$cancelType] =
                ($stats['cancel_type_counts'][$cancelType] ?? 0) + 1;

            // Count by cancel status
            $cancelStatus = $cancellation['cancel_status'] ?? 'UNKNOWN';
            $stats['cancel_status_counts'][$cancelStatus] =
                ($stats['cancel_status_counts'][$cancelStatus] ?? 0) + 1;

            // Sum total refund amount
            if (isset($cancellation['refund_amount']['refund_total'])) {
                $stats['total_refund_amount'] += (float) $cancellation['refund_amount']['refund_total'];
            }

            // Count by currency
            $currency = $cancellation['refund_amount']['currency'] ?? 'UNKNOWN';
            $stats['currency_counts'][$currency] =
                ($stats['currency_counts'][$currency] ?? 0) + 1;
        }

        return $stats;
    }
}
