<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Partner;

use App\Models\OAuthState;
use App\Models\ProviderToken;
use App\Models\Shop;
use App\Services\TikTokShopTokenService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TikTokAuthController extends BasePartnerController
{
    use ApiResponseTrait;

    protected TikTokShopTokenService $tokenService;

    public function __construct(TikTokShopTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Generate authorization URL
     */
    public function getAuthUrl(Request $request): JsonResponse
    {
        try {
            $this->validateRequiredParams($request, ['shop_id']);

            $shopId = $request->get('shop_id');
            $state = Str::random(32);

            // Store state in database
            OAuthState::create([
                'state' => $state,
                'shop_id' => $shopId,
                'expires_at' => now()->addMinutes(10),
            ]);

            $authUrl = $this->buildAuthUrl($state);

            return $this->successResponse([
                'auth_url' => $authUrl,
                'state' => $state,
                'expires_in' => 600,
            ]);

        } catch (ValidationException $e) {
            return $this->validationError($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to generate auth URL', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('API_ERROR', 'Không thể tạo URL ủy quyền', null, [], 500);
        }
    }

    /**
     * Handle authorization callback
     */
    public function handleCallback(Request $request): JsonResponse
    {
        try {
            $this->validateRequiredParams($request, ['code', 'state']);

            $code = $request->get('code');
            $state = $request->get('state');

            // Verify state
            $oauthState = OAuthState::where('state', $state)
                ->where('expires_at', '>', now())
                ->first();

            if (! $oauthState) {
                return $this->validationError('State không hợp lệ hoặc đã hết hạn');
            }

            $shopId = $oauthState->shop_id;

            // Exchange code for token
            $tokenData = $this->exchangeCodeForToken($code);

            // Store token in database
            $this->storeToken($shopId, $tokenData);

            // Clean up state
            $oauthState->delete();

            return $this->successResponse([
                'message' => 'Ủy quyền thành công',
                'shop_id' => $shopId,
                'expires_at' => $tokenData['expires_at'] ?? null,
            ]);

        } catch (ValidationException $e) {
            return $this->validationError($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to handle auth callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('API_ERROR', 'Xử lý callback thất bại', null, [], 500);
        }
    }

    /**
     * Get authorization status
     */
    public function getStatus(Request $request): JsonResponse
    {
        try {
            $this->validateRequiredParams($request, ['shop_id']);

            $shopId = $request->get('shop_id');
            $shop = Shop::where('shop_id', $shopId)->first();

            if (! $shop) {
                return $this->notFoundError('Shop không tồn tại');
            }

            $token = ProviderToken::where('shop_id', $shopId)
                ->where('provider', 'tiktok_shop')
                ->where('expires_at', '>', now())
                ->first();

            $isAuthorized = $token !== null;
            $expiresAt = $token?->expires_at;

            return $this->successResponse([
                'shop_id' => $shopId,
                'is_authorized' => $isAuthorized,
                'expires_at' => $expiresAt,
                'scopes' => $token?->scopes ?? [],
            ]);

        } catch (ValidationException $e) {
            return $this->validationError($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to get auth status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('API_ERROR', 'Không thể lấy trạng thái ủy quyền', null, [], 500);
        }
    }

    /**
     * Revoke authorization
     */
    public function revoke(Request $request): JsonResponse
    {
        try {
            $this->validateRequiredParams($request, ['shop_id']);

            $shopId = $request->get('shop_id');

            DB::transaction(function () use ($shopId) {
                // Delete tokens
                ProviderToken::where('shop_id', $shopId)
                    ->where('provider', 'tiktok_shop')
                    ->delete();

                // Update shop status
                Shop::where('shop_id', $shopId)
                    ->update(['is_active' => false]);
            });

            return $this->successResponse([
                'message' => 'Hủy ủy quyền thành công',
                'shop_id' => $shopId,
            ]);

        } catch (ValidationException $e) {
            return $this->validationError($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to revoke authorization', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('API_ERROR', 'Hủy ủy quyền thất bại', null, [], 500);
        }
    }

    /**
     * Build authorization URL
     */
    protected function buildAuthUrl(string $state): string
    {
        $params = [
            'client_key' => config('services.tiktok_shop.client_key'),
            'scope' => implode(',', config('services.tiktok_shop.scopes')),
            'response_type' => 'code',
            'redirect_uri' => config('services.tiktok_shop.redirect_uri'),
            'state' => $state,
        ];

        return 'https://auth.tiktok-shops.com/oauth/authorize?'.http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    protected function exchangeCodeForToken(string $code): array
    {
        $response = $this->tokenService->exchangeCodeForToken($code);

        if (! $response || ! isset($response['access_token'])) {
            throw new \RuntimeException('Không thể lấy access token');
        }

        return $response;
    }

    /**
     * Store token in database
     */
    protected function storeToken(string $shopId, array $tokenData): void
    {
        DB::transaction(function () use ($shopId, $tokenData) {
            // Delete existing tokens
            ProviderToken::where('shop_id', $shopId)
                ->where('provider', 'tiktok_shop')
                ->delete();

            // Create new token
            ProviderToken::create([
                'shop_id' => $shopId,
                'provider' => 'tiktok_shop',
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
                'scopes' => $tokenData['scope'] ?? [],
            ]);

            // Update shop status
            Shop::where('shop_id', $shopId)
                ->update(['is_active' => true]);
        });
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
}
