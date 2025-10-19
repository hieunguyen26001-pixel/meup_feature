<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Partner;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductApiController extends BasePartnerController
{
    /**
     * Get products from TikTok Shop API
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $shopId = $this->getShopId($request);
            $pagination = $this->getPaginationParams($request);

            $products = $this->fetchProductsFromTikTok($shopId, $request, $pagination);

            return $this->formatResponse([
                'products' => $products['products'] ?? [],
                'total_count' => $products['total_count'] ?? 0,
                'next_page_token' => $products['next_page_token'] ?? null,
            ], $shopId);

        } catch (ValidationException $e) {
            return $this->validationError($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->validationError($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleApiError($e, 'tải dữ liệu products');
        }
    }

    /**
     * Get product details by ID
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $this->validateRequiredParams($request, ['product_id']);

            $productId = $request->get('product_id');
            $shopId = $this->getShopId($request);

            $productDetails = $this->fetchProductDetailsFromTikTok($shopId, $productId);

            return $this->formatResponse($productDetails, $shopId);

        } catch (ValidationException $e) {
            return $this->validationError($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->validationError($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleApiError($e, 'lấy chi tiết product');
        }
    }

    /**
     * Get product statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $shopId = $this->getShopId($request);
            $pagination = $this->getPaginationParams($request);

            $products = $this->fetchProductsFromTikTok($shopId, $request, $pagination);
            $productsList = $products['products'] ?? [];

            $stats = $this->calculateProductStats($productsList);

            return $this->formatResponse($stats, $shopId);

        } catch (\InvalidArgumentException $e) {
            return $this->validationError($e->getMessage());
        } catch (\Exception $e) {
            return $this->handleApiError($e, 'tính toán thống kê products');
        }
    }

    /**
     * Fetch products from TikTok Shop API
     */
    protected function fetchProductsFromTikTok(
        string $shopId,
        Request $request,
        array $pagination
    ): array {
        $token = $this->getValidToken($shopId);
        $shop = $this->getShop($shopId);

        $params = $this->buildProductParams($shop, $pagination, $request);
        $bodyData = $this->buildProductBodyData($request);

        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/product/202309/products/search',
            json_encode($bodyData),
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        $url = 'https://open-api.tiktokglobalshop.com/product/202309/products/search?'.
               http_build_query($signatureData['signed_query']);

        return $this->makeTikTokApiRequest(
            $url,
            array_merge($params, ['access_token' => $token->access_token]),
            json_encode($bodyData),
            'POST'
        );
    }

    /**
     * Fetch product details from TikTok Shop API
     */
    protected function fetchProductDetailsFromTikTok(string $shopId, string $productId): array
    {
        $token = $this->getValidToken($shopId);
        $shop = $this->getShop($shopId);

        $params = [
            'shop_cipher' => $shop->seller_cipher,
            'product_id' => $productId,
            'app_key' => config('services.tiktok_shop.client_key'),
            'timestamp' => time(),
        ];

        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/product/202309/products',
            null,
            'application/json',
            config('services.tiktok_shop.client_secret')
        );

        $url = 'https://open-api.tiktokglobalshop.com/product/202309/products?'.
               http_build_query($signatureData['signed_query']);

        return $this->makeTikTokApiRequest(
            $url,
            array_merge($params, ['access_token' => $token->access_token])
        );
    }

    /**
     * Build product parameters
     */
    protected function buildProductParams(object $shop, array $pagination, Request $request): array
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
     * Build product body data
     */
    protected function buildProductBodyData(Request $request): array
    {
        $bodyData = [
            'locale' => $request->get('locale', 'en-US'),
        ];

        // Add optional filters
        $optionalFilters = [
            'product_ids',
            'category_ids',
            'brand_ids',
            'product_status',
            'create_time_ge',
            'create_time_lt',
            'update_time_ge',
            'update_time_lt',
        ];

        foreach ($optionalFilters as $filter) {
            if ($request->has($filter) && ! empty($request->get($filter))) {
                $bodyData[$filter] = $request->get($filter);
            }
        }

        return $bodyData;
    }

    /**
     * Calculate product statistics
     */
    protected function calculateProductStats(array $products): array
    {
        $stats = [
            'total_products' => count($products),
            'status_counts' => [],
            'category_counts' => [],
            'brand_counts' => [],
            'price_ranges' => [
                'under_100k' => 0,
                '100k_500k' => 0,
                '500k_1m' => 0,
                'over_1m' => 0,
            ],
        ];

        foreach ($products as $product) {
            // Count by status
            $status = $product['product_status'] ?? 'UNKNOWN';
            $stats['status_counts'][$status] =
                ($stats['status_counts'][$status] ?? 0) + 1;

            // Count by category
            $categoryId = $product['category_id'] ?? 'UNKNOWN';
            $stats['category_counts'][$categoryId] =
                ($stats['category_counts'][$categoryId] ?? 0) + 1;

            // Count by brand
            $brandId = $product['brand_id'] ?? 'UNKNOWN';
            $stats['brand_counts'][$brandId] =
                ($stats['brand_counts'][$brandId] ?? 0) + 1;

            // Count by price range
            $price = (float) ($product['price']['amount'] ?? 0);
            if ($price < 100000) {
                $stats['price_ranges']['under_100k']++;
            } elseif ($price < 500000) {
                $stats['price_ranges']['100k_500k']++;
            } elseif ($price < 1000000) {
                $stats['price_ranges']['500k_1m']++;
            } else {
                $stats['price_ranges']['over_1m']++;
            }
        }

        return $stats;
    }
}
