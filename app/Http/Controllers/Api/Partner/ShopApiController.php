<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Partner;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShopApiController extends BasePartnerController
{
    /**
     * Get shops from TikTok Shop API
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $shops = $this->fetchShopsFromTikTok($request);

            return $this->formatResponse([
                'shops' => $shops['shops'] ?? [],
                'total_count' => $shops['total_count'] ?? 0,
            ], 'all');

        } catch (ValidationException $e) {
            return $this->validationError($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->validationError($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleApiError($e, 'tải dữ liệu shops');
        }
    }

    /**
     * Get shop details by ID
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $this->validateRequiredParams($request, ['shop_id']);

            $shopId = $request->get('shop_id');
            $shopDetails = $this->fetchShopDetailsFromTikTok($shopId);

            return $this->formatResponse($shopDetails, $shopId);

        } catch (ValidationException $e) {
            return $this->validationError($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->validationError($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleApiError($e, 'lấy chi tiết shop');
        }
    }

    /**
     * Get shop statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $shops = $this->fetchShopsFromTikTok($request);
            $shopsList = $shops['shops'] ?? [];

            $stats = $this->calculateShopStats($shopsList);

            return $this->formatResponse($stats, 'all');

        } catch (\InvalidArgumentException $e) {
            return $this->validationError($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleApiError($e, 'tính toán thống kê shops');
        }
    }

    /**
     * Fetch shops from TikTok Shop API
     */
    protected function fetchShopsFromTikTok(Request $request): array
    {
        $token = $this->getValidToken($request->get('shop_id'));

        $params = [
            'app_key' => config('services.tiktok_shop.client_key'),
            'timestamp' => time(),
        ];

        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/shop/202309/shops',
            null,
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        $url = 'https://open-api.tiktokglobalshop.com/shop/202309/shops?'.
               http_build_query($signatureData['signed_query']);

        return $this->makeTikTokApiRequest(
            $url,
            array_merge($params, ['access_token' => $token->access_token])
        );
    }

    /**
     * Fetch shop details from TikTok Shop API
     */
    protected function fetchShopDetailsFromTikTok(string $shopId): array
    {
        $token = $this->getValidToken($shopId);

        $params = [
            'shop_id' => $shopId,
            'app_key' => config('services.tiktok_shop.client_key'),
            'timestamp' => time(),
        ];

        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/shop/202309/shop',
            null,
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        $url = 'https://open-api.tiktokglobalshop.com/shop/202309/shop?'.
               http_build_query($signatureData['signed_query']);

        return $this->makeTikTokApiRequest(
            $url,
            array_merge($params, ['access_token' => $token->access_token])
        );
    }

    /**
     * Calculate shop statistics
     */
    protected function calculateShopStats(array $shops): array
    {
        $stats = [
            'total_shops' => count($shops),
            'status_counts' => [],
            'region_counts' => [],
            'seller_type_counts' => [],
            'active_shops' => 0,
            'inactive_shops' => 0,
        ];

        foreach ($shops as $shop) {
            // Count by status
            $status = $shop['status'] ?? 'UNKNOWN';
            $stats['status_counts'][$status] =
                ($stats['status_counts'][$status] ?? 0) + 1;

            // Count by region
            $region = $shop['region'] ?? 'UNKNOWN';
            $stats['region_counts'][$region] =
                ($stats['region_counts'][$region] ?? 0) + 1;

            // Count by seller type
            $sellerType = $shop['seller_type'] ?? 'UNKNOWN';
            $stats['seller_type_counts'][$sellerType] =
                ($stats['seller_type_counts'][$sellerType] ?? 0) + 1;

            // Count active/inactive
            if ($status === 'ACTIVE') {
                $stats['active_shops']++;
            } else {
                $stats['inactive_shops']++;
            }
        }

        return $stats;
    }
}
