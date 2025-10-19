<?php

namespace App\Services;

use App\Models\ProviderToken;
use App\Models\Shop;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class TikTokShopTokenService
{
    private string $clientKey;
    private string $clientSecret;
    private string $redirectUri;
    private string $authBase;
    private string $apiBase;
    private int $refreshAhead;

    public function __construct()
    {
        $this->clientKey = config('services.tiktok_shop.client_key');
        $this->clientSecret = config('services.tiktok_shop.client_secret');
        $this->redirectUri = config('services.tiktok_shop.redirect_uri');
        $this->authBase = config('services.tiktok_shop.auth_base');
        $this->apiBase = config('services.tiktok_shop.api_base');
        $this->refreshAhead = config('services.tiktok_shop.refresh_ahead', 600);
    }

    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken(string $code): array
    {
        // Official TikTok Shop documentation parameters
        $requestData = [
            'app_key' => config('services.tiktok_shop.client_key'),  // TikTok Shop App key
            'app_secret' => $this->clientSecret,  // TikTok Shop App secret
            'auth_code' => $code,                 // Authorization code from callback
            'grant_type' => 'authorized_code', // OAuth 2.0 grant type
        ];

        Log::info('TikTok Shop Token Exchange Request', [
            'url' => 'https://auth.tiktok-shops.com/api/v2/token/get',
            'client_key' => $this->clientKey,
            'code_length' => strlen($code),
        ]);

        $response = Http::get('https://auth.tiktok-shops.com/api/v2/token/get', $requestData);

        Log::info('TikTok Shop Token Exchange Response', [
            'status' => $response->status(),
            'headers' => $response->headers(),
            'body_length' => strlen($response->body()),
            'json' => $response->json(),
        ]);


        if (!$response->successful()) {
            $responseData = $response->json() ?? [];
            throw new Exception('Token exchange failed: ' . json_encode($responseData));
        }

        $data = $response->json();

        // Handle different response structures
        $tokenData = null;
        if (isset($data['data']['access_token'])) {
            $tokenData = $data['data'];
        } elseif (isset($data['access_token'])) {
            $tokenData = $data;
        } elseif (isset($data['result']['access_token'])) {
            $tokenData = $data['result'];
        }

        if (!$tokenData || !isset($tokenData['access_token'])) {
            throw new Exception('No access token received: ' . json_encode($data));
        }

        return $tokenData;
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshToken(string $refreshToken): array
    {
        $requestData = [
            'app_key' => config('services.tiktok_shop.client_key'),  // TikTok Shop App key
            'app_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ];

        Log::info('TikTok Shop Token Refresh Request', [
            'url' => 'https://auth.tiktok-shops.com/api/v2/token/refresh',
            'client_key' => $this->clientKey,
        ]);

        $response = Http::get('https://auth.tiktok-shops.com/api/v2/token/refresh', $requestData);

        Log::info('TikTok Shop Token Refresh Response', [
            'status' => $response->status(),
            'headers' => $response->headers(),
        ]);

        if (!$response->successful()) {
            $responseData = $response->json() ?? [];
            throw new Exception('Token refresh failed: ' . json_encode($responseData));
        }

        $data = $response->json();

        // Handle different response structures
        $tokenData = null;
        if (isset($data['data']['access_token'])) {
            $tokenData = $data['data'];
        } elseif (isset($data['access_token'])) {
            $tokenData = $data;
        } elseif (isset($data['result']['access_token'])) {
            $tokenData = $data['result'];
        }

        if (!$tokenData || !isset($tokenData['access_token'])) {
            throw new Exception('No access token received from refresh: ' . json_encode($data));
        }

        return $tokenData;
    }

    /**
     * Get shop information using access token
     */
    public function getShopInfo(string $accessToken): array
    {
        $params = [
            'access_token' => $accessToken,
            'app_key' => config('services.tiktok_shop.client_key'), // TikTok Shop App key
            'version' => '202309',
        ];

        // Generate signature (includes timestamp generation)
        [$signature, $timestamp] = $this->generateSignature($params, '/authorization/202309/shops');

        $params['timestamp'] = $timestamp;
        $params['sign'] = $signature;

        // Use correct API endpoint: /authorization/202309/shops
        $response = Http::withHeaders([
            'content-type' => 'application/json',
            'x-tts-access-token' => $accessToken,
        ])->get($this->apiBase . '/authorization/202309/shops', $params);

        if (!$response->successful()) {
            $responseData = $response->json() ?? [];
            $errorCode = $responseData['code'] ?? null;
            $errorMessage = $responseData['message'] ?? 'Unknown error';

            // Handle timestamp-related errors
            if (str_contains($errorMessage, 'timestamp') || str_contains($errorMessage, 'expired')) {
                Log::warning('TikTok Shop timestamp error detected', [
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'request_timestamp' => $timestamp,
                    'current_time' => time(),
                    'time_difference' => time() - $timestamp,
                ]);

                throw new Exception('TIMESTAMP_ERROR: ' . $errorMessage);
            }

            throw new Exception('Get shop info failed: ' . json_encode($responseData));
        }

        return $response->json();
    }

    /**
     * Generate signature for TikTok Shop API calls
     * Based on official TikTok Shop Node.js SDK
     */
    public function generateSignature(array $params, string $apiPath = '/authorization/202309/shops', ?string $body = null, ?string $contentType = null): array
    {
        // Step 1: Generate fresh timestamp (Unix seconds, UTC)
        $timestamp = time(); // Unix timestamp in seconds (10 digits)

        // Step 2: Add timestamp to params
        $params['timestamp'] = $timestamp;

        // Step 3: Extract all query parameters excluding sign and access_token
        $excludeKeys = ['access_token', 'sign'];
        $filteredParams = $params;
        foreach ($excludeKeys as $key) {
            unset($filteredParams[$key]);
        }

        // Step 4: Reorder the parameter keys in alphabetical order
        ksort($filteredParams);

        // Step 5: Concatenate all the parameters in the format {key}{value}
        $paramString = '';
        foreach ($filteredParams as $key => $value) {
            $paramString .= $key . $value;
        }

        // Step 6: Try different format - apiPath + concatenated params + body
        $signString = $apiPath . $paramString;

        // Step 7: If the request header content-type is not multipart/form-data, append the API request body
        if ($contentType !== 'multipart/form-data' && $body && !empty($body)) {
            $signString .= $body; // Body is already a JSON string
        }

        // Step 8: Wrap the string generated in Step 7 with the app_secret
        $signString = $this->clientSecret . $signString . $this->clientSecret;

        // Step 9: Encode your wrapped string using HMAC-SHA256
        $signature = hash_hmac('sha256', $signString, $this->clientSecret);

        Log::info('TikTok Shop Signature Generation (Official Algorithm)', [
            'timestamp' => $timestamp,
            'timestamp_human' => date('Y-m-d H:i:s', $timestamp),
            'params' => $params,
            'filtered_params' => $filteredParams,
            'param_string' => $paramString,
            'api_path' => $apiPath,
            'body' => $body,
            'content_type' => $contentType,
            'sign_string' => $signString,
            'sign_string_length' => strlen($signString),
            'signature' => $signature,
            'algorithm' => 'HMAC-SHA256'
        ]);

        return [$signature, $timestamp];
    }

    /**
     * Tạo signature TikTok Shop (HMAC-SHA256) – chuẩn theo tài liệu.
     *
     * @param array $qs Query sẽ GỬI thật (KHÔNG include sign/access_token; nếu có hàm sẽ bỏ).
     * @param string $apiPath Path thuần bắt đầu bằng '/', vd: '/product/202309/products/search'
     * @param string|null $rawBody Raw body GỬI thật (JSON minify). Để null nếu không có body hoặc multipart.
     * @param string|null $contentType Content-Type dự định gửi.
     * @param string $appSecret App secret TikTok.
     * @param bool $autoTs Tự chèn timestamp nếu thiếu.
     *
     * @return array{sign:string, timestamp:int, signed_query:array}
     */
    function tts_sign(
        array   $qs,
        string  $apiPath,
        ?string $rawBody,
        ?string $contentType,
        string  $appSecret,
        bool    $autoTs = true
    ): array
    {
        if ($apiPath === '' || $apiPath[0] !== '/') {
            $apiPath = '/' . ltrim($apiPath, '/');
        }

        if ($autoTs && !array_key_exists('timestamp', $qs)) {
            $qs['timestamp'] = time();
        }

        unset($qs['sign'], $qs['access_token']);
        // loại param rỗng khỏi query
        $qs = array_filter($qs, fn($v) => $v !== null && $v !== '');

        ksort($qs);

        $paramString = '';
        foreach ($qs as $k => $v) {
            if (is_bool($v)) $v = $v ? 'true' : 'false';
            elseif ($v === null) $v = '';
            elseif (is_array($v)) $v = implode(',', array_map(fn($x) => (string)$x, $v));
            elseif (is_object($v)) $v = json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            else                      $v = (string)$v;
            $paramString .= $k . $v;
        }

        $signStr = $apiPath . $paramString;

        $isMultipart = is_string($contentType) && stripos($contentType, 'multipart/form-data') !== false;
        if (!$isMultipart && $rawBody !== null && $rawBody !== '') {
            $signStr .= $rawBody; // raw JSON minify, đúng chuỗi gửi
        }

        $wrapped = $appSecret . $signStr . $appSecret;
        $sign = hash_hmac('sha256', $wrapped, $appSecret);

        $qs['sign'] = $sign;

        return ['sign' => $sign, 'timestamp' => $qs['timestamp'], 'signed_query' => $qs];
    }

    /**
     * Get shop info with auto-refresh token logic
     */
    public function getShopInfoWithAutoRefresh(?string $shopId = null, bool $forceRefresh = false): array
    {
        // Step 1: Get token (either by shop_id or infer from available tokens)
        $token = $this->getValidToken($shopId);

        if (!$token) {
            throw new Exception('TOKEN_NOT_FOUND');
        }

        // Step 2: Check if token needs refresh
        if ($token->needsRefresh($this->refreshAhead)) {
            try {
                $this->refreshTokenForShop($token);
                $token->refresh(); // Reload from database
            } catch (Exception $e) {
                Log::error('Token refresh failed', [
                    'shop_id' => $token->subject_id,
                    'error' => $e->getMessage(),
                ]);
                throw new Exception('TOKEN_EXPIRED');
            }
        }

        // Step 3: Check cache if not forcing refresh
        if (!$forceRefresh) {
            $cachedInfo = $this->getCachedShopInfo($token->subject_id);
            if ($cachedInfo) {
                $cachedInfo['source'] = 'cache';
                return $cachedInfo;
            }
        }

        // Step 4: Call TikTok API
        try {
            $shopInfo = $this->getShopInfo($token->access_token);
            $normalizedInfo = $this->normalizeShopInfo($shopInfo, $token);

            // Step 5: Cache the result
            $this->cacheShopInfo($token->subject_id, $normalizedInfo);

            $normalizedInfo['source'] = 'provider';
            return $normalizedInfo;

        } catch (Exception $e) {
            Log::error('TikTok Shop API call failed', [
                'shop_id' => $token->subject_id,
                'error' => $e->getMessage(),
            ]);

            // Check if it's a scope issue
            if (str_contains($e->getMessage(), 'scope') || str_contains($e->getMessage(), 'permission')) {
                throw new Exception('OAUTH_SCOPE_FILTERED');
            }

            throw $e;
        }
    }

    /**
     * Get valid token for shop (with inference logic)
     */
    public function getValidToken(?string $shopId = null): ?ProviderToken
    {
        if ($shopId) {
            // Direct lookup by shop_id
            return ProviderToken::findByProviderAndSubject('SHOP', $shopId);
        }

        // Infer shop_id from available tokens
        $tokens = ProviderToken::where('provider', 'SHOP')
            ->where('expires_at', '>', now())
            ->get();

        if ($tokens->isEmpty()) {
            return null;
        }

        if ($tokens->count() === 1) {
            return $tokens->first();
        }

        // Multiple shops - for now, return the first one
        // TODO: Implement user preference or default shop logic
        Log::warning('Multiple shops found, using first one', [
            'shop_count' => $tokens->count(),
            'shops' => $tokens->pluck('subject_id')->toArray(),
        ]);

        return $tokens->first();
    }

    /**
     * Refresh token for a specific shop
     */
    private function refreshTokenForShop(ProviderToken $token): void
    {
        if (!$token->refresh_token) {
            throw new Exception('No refresh token available');
        }

        $newTokenData = $this->refreshToken($token->refresh_token);
        $token->updateToken($newTokenData);
    }

    /**
     * Normalize shop info response to standard format
     */
    private function normalizeShopInfo(array $apiResponse, ProviderToken $token): array
    {
        // Extract shop info from TikTok API response
        $shopData = $apiResponse['data'] ?? $apiResponse;

        return [
            'shop_id' => $shopData['shop_id'] ?? $token->subject_id,
            'region' => $shopData['region'] ?? $shopData['market'] ?? 'VN',
            'shop_name' => $shopData['shop_name'] ?? $shopData['name'] ?? 'Unknown Shop',
            'shop_logo' => $shopData['shop_logo'] ?? $shopData['logo'] ?? null,
            'status' => $shopData['status'] ?? 'ACTIVE',
            'seller_id' => $shopData['seller_id'] ?? $shopData['seller'] ?? null,
            'created_at' => $shopData['created_at'] ?? null,
            'updated_at' => $shopData['updated_at'] ?? now()->toISOString(),
            'authorized_scopes' => $this->parseScopes($token->scope),
        ];
    }

    /**
     * Parse scopes string to array
     */
    private function parseScopes(?string $scopeString): ?array
    {
        if (!$scopeString) {
            return null;
        }

        return array_map('trim', explode(',', $scopeString));
    }

    /**
     * Get cached shop info
     */
    private function getCachedShopInfo(string $shopId): ?array
    {
        // TODO: Implement Redis/cache storage
        // For now, return null to always call API
        return null;
    }

    /**
     * Cache shop info
     */
    private function cacheShopInfo(string $shopId, array $shopInfo): void
    {
        // TODO: Implement Redis/cache storage
        // For now, do nothing
    }

    /**
     * Get authorized scopes
     */
    public function getAuthorizedScopes(string $accessToken): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->get($this->apiBase . '/shop/get_authorized_scopes');

        if (!$response->successful()) {
            $responseData = $response->json() ?? [];
            throw new Exception('Get authorized scopes failed: ' . json_encode($responseData));
        }

        return $response->json();
    }

    /**
     * Ensure token is valid and refresh if needed
     */
    public function ensureValidToken(string $shopId): ?ProviderToken
    {
        $token = ProviderToken::findByProviderAndSubject('SHOP', $shopId);

        if (!$token) {
            return null;
        }

        // Check if token needs refresh
        if ($token->needsRefresh($this->refreshAhead)) {
            if ($token->isRefreshTokenExpired()) {
                // Refresh token is also expired, need re-authorization
                return null;
            }

            try {
                $newTokenData = $this->refreshToken($token->refresh_token);
                $token->updateToken($newTokenData);

                Log::info('Token refreshed successfully', [
                    'shop_id' => $shopId,
                    'expires_at' => $token->expires_at,
                ]);
            } catch (Exception $e) {
                Log::error('Token refresh failed', [
                    'shop_id' => $shopId,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        }

        return $token;
    }

    /**
     * Save token to database
     *
     * TikTok Shop OAuth returns expiration timestamps as Unix epoch (seconds)
     * These represent absolute expiration times, not relative durations
     */
    public function saveToken(string $shopId, array $tokenData): ProviderToken
    {
        // Xử lý format của TikTok Shop API
        $processedTokenData = [
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? null,
            'scope' => isset($tokenData['granted_scopes']) ? implode(',', $tokenData['granted_scopes']) : null,
        ];

        // TikTok Shop API trả về Unix timestamp (seconds) cho expiration
        // These are absolute expiration times, not relative durations
        if (isset($tokenData['access_token_expire_in'])) {
            $expirationTimestamp = $tokenData['access_token_expire_in'];

            // Debug: Log raw timestamp from TikTok
            Log::info('TikTok Shop Raw Token Data', [
                'shop_id' => $shopId,
                'raw_token_data' => $tokenData,
                'access_token_expire_in' => $expirationTimestamp,
                'timestamp_length' => strlen((string)$expirationTimestamp),
                'timestamp_type' => gettype($expirationTimestamp),
            ]);

            // Check if timestamp is in milliseconds (13 digits) or seconds (10 digits)
            if (strlen((string)$expirationTimestamp) === 13) {
                // Convert milliseconds to seconds
                $expirationTimestamp = $expirationTimestamp / 1000;
                Log::info('Converted milliseconds to seconds', [
                    'original' => $tokenData['access_token_expire_in'],
                    'converted' => $expirationTimestamp,
                ]);
            }

            // Validate timestamp is reasonable (not too far in future)
            $currentTime = time();
            $maxFutureTime = $currentTime + (365 * 24 * 3600); // Max 1 year

            Log::info('Timestamp validation', [
                'expiration_timestamp' => $expirationTimestamp,
                'current_time' => $currentTime,
                'max_future_time' => $maxFutureTime,
                'expiration_human' => date('Y-m-d H:i:s', $expirationTimestamp),
                'is_valid' => ($expirationTimestamp > $currentTime && $expirationTimestamp <= $maxFutureTime),
            ]);

            if ($expirationTimestamp > $currentTime && $expirationTimestamp <= $maxFutureTime) {
                $processedTokenData['expires_at'] = \Carbon\Carbon::createFromTimestamp($expirationTimestamp);
                Log::info('Token expiration set from TikTok timestamp', [
                    'shop_id' => $shopId,
                    'expiration_timestamp' => $expirationTimestamp,
                    'expiration_human' => date('Y-m-d H:i:s', $expirationTimestamp),
                    'expires_in_days' => round(($expirationTimestamp - $currentTime) / (24 * 3600), 2),
                ]);
            } else {
                // Fallback: assume 7 days if timestamp is invalid
                $processedTokenData['expires_at'] = now()->addDays(7);
                Log::warning('Invalid expiration timestamp from TikTok, using fallback', [
                    'shop_id' => $shopId,
                    'invalid_timestamp' => $expirationTimestamp,
                    'invalid_human' => date('Y-m-d H:i:s', $expirationTimestamp),
                    'fallback_expiration' => $processedTokenData['expires_at'],
                ]);
            }
        }

        if (isset($tokenData['refresh_token_expire_in'])) {
            $refreshExpirationTimestamp = $tokenData['refresh_token_expire_in'];

            // Check if timestamp is in milliseconds (13 digits) or seconds (10 digits)
            if (strlen((string)$refreshExpirationTimestamp) === 13) {
                // Convert milliseconds to seconds
                $refreshExpirationTimestamp = $refreshExpirationTimestamp / 1000;
            }

            // Validate refresh token expiration (can be longer)
            $currentTime = time();
            $maxFutureTime = $currentTime + (10 * 365 * 24 * 3600); // Max 10 years

            if ($refreshExpirationTimestamp > $currentTime && $refreshExpirationTimestamp <= $maxFutureTime) {
                $processedTokenData['refresh_expires_at'] = \Carbon\Carbon::createFromTimestamp($refreshExpirationTimestamp);
                Log::info('Refresh token expiration set from TikTok timestamp', [
                    'shop_id' => $shopId,
                    'refresh_expiration_timestamp' => $refreshExpirationTimestamp,
                    'refresh_expiration_human' => date('Y-m-d H:i:s', $refreshExpirationTimestamp),
                ]);
            } else {
                // Fallback: assume 30 days for refresh token
                $processedTokenData['refresh_expires_at'] = now()->addDays(30);
                Log::warning('Invalid refresh expiration timestamp from TikTok, using fallback', [
                    'shop_id' => $shopId,
                    'invalid_refresh_timestamp' => $refreshExpirationTimestamp,
                    'fallback_refresh_expiration' => $processedTokenData['refresh_expires_at'],
                ]);
            }
        }

        return ProviderToken::upsertToken('SHOP', $shopId, $processedTokenData);
    }

    /**
     * Get list of authorized shops
     */
    public function getAuthorizedShops(): array
    {
        $shops = \App\Models\Shop::where('is_active', true)
            ->with('providerToken')
            ->get();
        $result = [];
        foreach ($shops as $shop) {

            $token = $shop->providerToken;

            if (!$token || !$token->access_token) {
                // Skip shops with expired tokens
                continue;
            }

            $result[] = [
                'shop_id' => $shop->shop_id,
                'shop_name' => $shop->shop_name,
                'region' => $shop->region,
                'seller_type' => $shop->seller_type,
                'scopes' => $shop->scopes ?? [],
                'status' => 'ACTIVE',
                'has_valid_token' => true,
                'token_expires_at' => $token->expires_at->toISOString(),
                'last_sync_at' => $shop->last_sync_at?->toISOString(),
                'authorized_at' => $shop->created_at->toISOString(),
            ];
        }

        return $result;
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): int
    {
        return ProviderToken::where('expires_at', '<', now())
            ->orWhere(function ($query) {
                $query->whereNotNull('refresh_expires_at')
                    ->where('refresh_expires_at', '<', now());
            })
            ->delete();
    }

    /**
     * Get active shop
     */
    public function getActiveShop(): ?Shop
    {
        return Shop::where('is_active', true)->first();
    }
}
