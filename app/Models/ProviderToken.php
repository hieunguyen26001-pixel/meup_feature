<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class ProviderToken extends Model
{
    use HasFactory;

    protected $table = 'provider_tokens';

    protected $fillable = [
        'provider',
        'subject_id',
        'access_token',
        'refresh_token',
        'scope',
        'expires_at',
        'refresh_expires_at',
    ];

    /**
     * @var string|null
     */
    public $access_token;

    /**
     * @var string|null
     */
    public $refresh_token;

    protected $casts = [
        'expires_at' => 'datetime',
        'refresh_expires_at' => 'datetime',
        'access_token' => 'string',
        'refresh_token' => 'string',
    ];

    /**
     * Check if access token is expired
     */
    public function isAccessTokenExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if refresh token is expired
     */
    public function isRefreshTokenExpired(): bool
    {
        if (!$this->refresh_expires_at) {
            return false; // No refresh token expiration
        }
        
        return $this->refresh_expires_at->isPast();
    }

    /**
     * Check if token needs refresh (before expiration)
     */
    public function needsRefresh(int $aheadSeconds = 600): bool
    {
        return $this->expires_at->subSeconds($aheadSeconds)->isPast();
    }

    /**
     * Update token data
     */
    public function updateToken(array $tokenData): bool
    {
        $updateData = [
            'access_token' => $tokenData['access_token'],
            'expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
        ];

        if (isset($tokenData['refresh_token'])) {
            $updateData['refresh_token'] = $tokenData['refresh_token'];
        }

        if (isset($tokenData['refresh_expires_in'])) {
            $updateData['refresh_expires_at'] = now()->addSeconds($tokenData['refresh_expires_in']);
        }

        if (isset($tokenData['scope'])) {
            $updateData['scope'] = $tokenData['scope'];
        }

        return $this->update($updateData);
    }

    /**
     * Find token by provider and subject_id
     */
    public static function findByProviderAndSubject(string $provider, string $subjectId): ?self
    {
        return self::where('provider', $provider)
                   ->where('subject_id', $subjectId)
                   ->first();
    }

    /**
     * Upsert token (create or update)
     */
    public static function upsertToken(string $provider, string $subjectId, array $tokenData): self
    {
        return self::updateOrCreate(
            ['provider' => $provider, 'subject_id' => $subjectId],
            array_merge($tokenData, [
                'expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
                'refresh_expires_at' => isset($tokenData['refresh_expires_in']) 
                    ? now()->addSeconds($tokenData['refresh_expires_in']) 
                    : null,
            ])
        );
    }
}
