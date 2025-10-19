<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'shop_name',
        'region',
        'seller_type',
        'seller_cipher',
        'scopes',
        'metadata',
        'is_active',
        'last_sync_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Get the provider token for this shop
     */
    public function providerToken()
    {
        return $this->hasOne(ProviderToken::class, 'subject_id', 'shop_id')
                    ->where('provider', 'SHOP');
    }

    /**
     * Check if shop has valid token
     */
    public function hasValidToken(): bool
    {
        $token = $this->providerToken;
        return $token && !$token->isAccessTokenExpired();
    }

    /**
     * Get shop by TikTok Shop ID
     */
    public static function findByShopId(string $shopId): ?self
    {
        return self::where('shop_id', $shopId)->first();
    }

    /**
     * Create or update shop from TikTok data
     */
    public static function upsertFromTikTok(array $shopData, array $tokenData): self
    {
        $scopes = $tokenData['granted_scopes'] ?? [];
        return self::updateOrCreate(
            ['shop_id' => $shopData['shop_id']],
            [
                'shop_name' => $shopData['shop_name'],
                'region' => $shopData['region'] ?? 'VN',
                'seller_type' => $shopData['seller_type'] ?? null,
                'seller_cipher' => $shopData['seller_cipher'] ?? null,
                'scopes' => $scopes,
                'metadata' => $shopData['metadata'] ?? null,
                'is_active' => true,
                'last_sync_at' => now(),
            ]
        );
    }
}
