<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class OAuthState extends Model
{
    use HasFactory;

    protected $table = 'oauth_states';

    protected $fillable = [
        'provider',
        'state',
        'redirect',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Check if state is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Create a new state with expiration
     */
    public static function createState(string $provider = 'SHOP', ?string $redirect = null, int $ttlMinutes = 10): self
    {
        $state = bin2hex(random_bytes(32));
        
        return self::create([
            'provider' => $provider,
            'state' => $state,
            'redirect' => $redirect,
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);
    }

    /**
     * Clean up expired states
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<', now())->delete();
    }
}
