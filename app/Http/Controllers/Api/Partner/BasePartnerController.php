<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Services\TikTokShopTokenService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

abstract class BasePartnerController extends Controller
{
    use ApiResponseTrait;

    protected TikTokShopTokenService $tokenService;

    protected const DEFAULT_PAGE_SIZE = 20;

    protected const MAX_PAGE_SIZE = 100;

    protected const DEFAULT_TIMEOUT = 30;

    public function __construct(TikTokShopTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Get shop ID from request or use first available shop
     */
    protected function getShopId(Request $request): string
    {
        $shopId = $request->get('shop_id');

        if (! $shopId) {
            $shop = Shop::where('is_active', true)->first();

            if (! $shop) {
                throw new \InvalidArgumentException('Không có shop nào được ủy quyền');
            }

            $shopId = $shop->shop_id;
        }

        return $shopId;
    }

    /**
     * Get pagination parameters with validation
     */
    protected function getPaginationParams(Request $request): array
    {
        $pageSize = (int) $request->get('page_size', self::DEFAULT_PAGE_SIZE);
        $pageToken = $request->get('page_token');

        // Validate page size
        if ($pageSize > self::MAX_PAGE_SIZE) {
            $pageSize = self::MAX_PAGE_SIZE;
        }

        if ($pageSize < 1) {
            $pageSize = self::DEFAULT_PAGE_SIZE;
        }

        return [
            'page_size' => $pageSize,
            'page_token' => $pageToken,
        ];
    }

    /**
     * Process date range from frontend
     */
    protected function processDateRange(Request $request): array
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $dateRange = [];

        if ($startDate) {
            $dateRange['create_time_ge'] = strtotime($startDate);
            $dateRange['update_time_ge'] = strtotime($startDate);
        }

        if ($endDate) {
            $dateRange['create_time_lt'] = strtotime($endDate.' 23:59:59');
            $dateRange['update_time_lt'] = strtotime($endDate.' 23:59:59');
        }

        return $dateRange;
    }

    /**
     * Get shop information
     */
    protected function getShop(string $shopId): Shop
    {
        $shop = Shop::where('shop_id', $shopId)->first();

        if (! $shop) {
            throw new \InvalidArgumentException("Shop {$shopId} không tồn tại");
        }

        return $shop;
    }

    /**
     * Get valid access token
     */
    protected function getValidToken(string $shopId): object
    {
        $token = $this->tokenService->getValidToken($shopId);

        if (! $token) {
            throw new \InvalidArgumentException('Không có token hợp lệ. Vui lòng ủy quyền lại.');
        }

        return $token;
    }

    /**
     * Make HTTP request to TikTok API with proper error handling
     */
    protected function makeTikTokApiRequest(
        string $url,
        array $params,
        ?string $body = null,
        string $method = 'GET'
    ): array {
        try {
            $response = Http::timeout(self::DEFAULT_TIMEOUT)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-tts-access-token' => $params['access_token'] ?? '',
                ])
                ->when($body, function ($http) use ($body) {
                    return $http->withBody($body, 'application/json');
                })
                ->$method($url);

            if (! $response->successful()) {
                $this->logApiError($url, $params, $response);
                throw new \RuntimeException('Yêu cầu API thất bại: '.$response->body());
            }

            $data = $response->json();

            if (! $data || ! isset($data['code']) || $data['code'] !== 0) {
                throw new \RuntimeException('API trả về lỗi: '.json_encode($data));
            }

            return $data['data'] ?? [];

        } catch (\Exception $e) {
            Log::error('TikTok API request failed', [
                'url' => $url,
                'params' => $params,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Log API error with context
     */
    protected function logApiError(string $url, array $params, $response): void
    {
        Log::error('TikTok API failed', [
            'url' => $url,
            'status' => $response->status(),
            'body' => $response->body(),
            'params' => $this->sanitizeParams($params),
        ]);
    }

    /**
     * Sanitize parameters for logging (remove sensitive data)
     */
    protected function sanitizeParams(array $params): array
    {
        $sensitiveKeys = ['access_token', 'client_secret', 'password'];

        return array_filter($params, function ($key) use ($sensitiveKeys) {
            return ! in_array($key, $sensitiveKeys);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Handle common API errors
     */
    protected function handleApiError(\Exception $e, string $context = ''): JsonResponse
    {
        $message = $context ? "Lỗi khi {$context}: ".$e->getMessage() : $e->getMessage();

        Log::error('API Error', [
            'context' => $context,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return $this->errorResponse('API_ERROR', $message, null, [], 500);
    }

    /**
     * Validate required parameters
     */
    protected function validateRequiredParams(Request $request, array $required): void
    {
        $missing = [];

        foreach ($required as $param) {
            if (! $request->has($param) || $request->get($param) === null) {
                $missing[] = $param;
            }
        }

        if (! empty($missing)) {
            throw ValidationException::withMessages([
                'required' => 'Thiếu các tham số bắt buộc: '.implode(', ', $missing),
            ]);
        }
    }

    /**
     * Format response data
     */
    protected function formatResponse(array $data, string $shopId, string $source = 'tiktok_api'): JsonResponse
    {
        return $this->successResponse([
            'data' => $data,
            'shop_id' => $shopId,
            'source' => $source,
        ]);
    }

    /**
     * Helper method for validation errors
     */
    protected function validationError(string $message): JsonResponse
    {
        return $this->errorResponse('VALIDATION_ERROR', $message, 'Kiểm tra lại các tham số gửi lên');
    }

    /**
     * Helper method for not found errors
     */
    protected function notFoundError(string $message): JsonResponse
    {
        return $this->errorResponse('NOT_FOUND', $message, 'Kiểm tra lại ID được cung cấp', [], 404);
    }
}
