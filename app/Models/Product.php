<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'tiktok_product_id',
        'shop_id',
        'title',
        'description',
        'status',
        'images',
        'skus',
        'categories',
        'attributes',
        'min_price',
        'max_price',
        'total_stock',
        'tiktok_created_at',
        'tiktok_updated_at',
        'last_synced_at',
    ];

    protected $casts = [
        'images' => 'array',
        'skus' => 'array',
        'categories' => 'array',
        'attributes' => 'array',
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'tiktok_created_at' => 'datetime',
        'tiktok_updated_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Relationship với Shop
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }

    /**
     * Scope để lấy sản phẩm theo shop
     */
    public function scopeForShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope để lấy sản phẩm theo trạng thái
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope để lấy sản phẩm đã sync gần đây
     */
    public function scopeRecentlySynced($query, $hours = 24)
    {
        return $query->where('last_synced_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope để lấy sản phẩm cần sync
     */
    public function scopeNeedsSync($query, $hours = 24)
    {
        return $query->where(function ($q) use ($hours) {
            $q->whereNull('last_synced_at')
              ->orWhere('last_synced_at', '<', now()->subHours($hours));
        });
    }

    /**
     * Get formatted price range
     */
    public function getFormattedPriceRangeAttribute(): string
    {
        if (!$this->min_price && !$this->max_price) {
            return 'N/A';
        }

        if ($this->min_price == $this->max_price) {
            return number_format($this->min_price, 0, ',', '.') . '<sup>đ</sup>';
        }

        return number_format($this->min_price, 0, ',', '.') . '<sup>đ</sup> - ' . 
               number_format($this->max_price, 0, ',', '.') . '<sup>đ</sup>';
    }

    /**
     * Get first product image
     */
    public function getFirstImageAttribute(): ?string
    {
        if (!$this->images || !is_array($this->images) || empty($this->images)) {
            return null;
        }

        $firstImage = $this->images[0];
        return $firstImage['url'] ?? $firstImage['image_url'] ?? null;
    }

    /**
     * Get first SKU
     */
    public function getFirstSkuAttribute(): ?string
    {
        if (!$this->skus || !is_array($this->skus) || empty($this->skus)) {
            return 'N/A';
        }

        return $this->skus[0]['seller_sku'] ?? 'N/A';
    }

    /**
     * Get status text in Vietnamese
     */
    public function getStatusTextAttribute(): string
    {
        $statusMap = [
            'ACTIVE' => 'Hoạt động',
            'INACTIVE' => 'Không hoạt động',
            'DRAFT' => 'Bản nháp',
            'PENDING' => 'Chờ duyệt',
            'REJECTED' => 'Từ chối',
            'DELETED' => 'Đã xóa',
        ];

        return $statusMap[$this->status] ?? $this->status;
    }

    /**
     * Get status CSS class
     */
    public function getStatusClassAttribute(): string
    {
        $classMap = [
            'ACTIVE' => 'bg-green-100 text-green-800',
            'INACTIVE' => 'bg-gray-100 text-gray-800',
            'DRAFT' => 'bg-yellow-100 text-yellow-800',
            'PENDING' => 'bg-blue-100 text-blue-800',
            'REJECTED' => 'bg-red-100 text-red-800',
            'DELETED' => 'bg-red-100 text-red-800',
        ];

        return $classMap[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get stock CSS class
     */
    public function getStockClassAttribute(): string
    {
        if ($this->total_stock <= 0) {
            return 'bg-red-100 text-red-800';
        } elseif ($this->total_stock <= 10) {
            return 'bg-yellow-100 text-yellow-800';
        } else {
            return 'bg-green-100 text-green-800';
        }
    }
}