<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Shop;
use App\Services\TikTokShopTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncTikTokProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok:sync-products
                            {--shop= : Shop ID cụ thể để sync}
                            {--all : Sync tất cả shops}
                            {--force : Force sync ngay cả khi đã sync gần đây}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync products từ TikTok Shop API vào database';

    protected TikTokShopTokenService $tokenService;

    public function __construct(TikTokShopTokenService $tokenService)
    {
        parent::__construct();
        $this->tokenService = $tokenService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Bắt đầu sync products từ TikTok Shop...');

        try {
            $shopId = $this->option('shop');
            $syncAll = $this->option('all');
            $force = $this->option('force');

            if ($shopId) {
                $this->syncShopProducts($shopId, $force);
            } elseif ($syncAll) {
                $this->syncAllShops($force);
            } else {
                $this->error('❌ Vui lòng chỉ định --shop=SHOP_ID hoặc --all');
                return 1;
            }

            $this->info('✅ Hoàn thành sync products!');
            return 0;

        } catch (\Throwable $e) {
            $this->error('❌ Lỗi khi sync products: ' . $e->getMessage());
            Log::error('Sync TikTok Products failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Sync products cho một shop cụ thể
     */
    private function syncShopProducts(string $shopId, bool $force = false): void
    {
        $this->info("📦 Sync products cho shop: {$shopId}");

        // Kiểm tra token hợp lệ
        if (!$this->tokenService->getValidToken($shopId)) {
            $this->warn("⚠️ Shop {$shopId} không có token hợp lệ, bỏ qua...");
            return;
        }

        // Kiểm tra xem có cần sync không
        if (!$force) {
            $lastSync = Product::forShop($shopId)
                ->whereNotNull('last_synced_at')
                ->max('last_synced_at');


            if ($lastSync && $lastSync->diffInHours(now()) < 1) {
                $this->info("ℹ️ Shop {$shopId} đã sync trong 1 giờ qua, bỏ qua...");
                return;
            }
        }

        $pageToken = null;
        $totalSynced = 0;
        $page = 1;

        do {
            $this->info("📄 Đang sync trang {$page}...");

            try {
                $response = $this->fetchProductsFromTikTok($shopId, $pageToken);


                if (!$response || !isset($response['data']['products'])) {
                    $this->warn("⚠️ Không có dữ liệu từ TikTok API");
                    break;
                }

                $products = $response['data']['products'];
                $synced = $this->processProducts($products, $shopId);
                $totalSynced += $synced;

                $this->info("✅ Đã sync {$synced} sản phẩm từ trang {$page}");

                $pageToken = $response['data']['next_page_token'] ?? null;
                $page++;

                // Giới hạn số trang để tránh timeout
                if ($page > 50) {
                    $this->warn("⚠️ Đã đạt giới hạn 50 trang, dừng sync");
                    break;
                }

            } catch (\Throwable $e) {
                $this->error("❌ Lỗi khi sync trang {$page}: " . $e->getMessage());
                Log::error("Sync page failed", [
                    'shop_id' => $shopId,
                    'page' => $page,
                    'error' => $e->getMessage()
                ]);
                break;
            }

        } while ($pageToken);

        $this->info("🎉 Hoàn thành sync shop {$shopId}: {$totalSynced} sản phẩm");
    }

    /**
     * Sync products cho tất cả shops
     */
    private function syncAllShops(bool $force = false): void
    {
        $this->info('🌍 Sync products cho tất cả shops...');

        $shops = Shop::where('is_active', true)->get();

        if ($shops->isEmpty()) {
            $this->warn('⚠️ Không có shop nào để sync');
            return;
        }

        $this->info("📊 Tìm thấy {$shops->count()} shops");

        foreach ($shops as $shop) {
            $this->syncShopProducts($shop->shop_id, $force);
            $this->newLine();
        }
    }

    /**
     * Fetch products từ TikTok API
     */
    private function fetchProductsFromTikTok(string $shopId, ?string $pageToken = null): ?array
    {
        $shop = Shop::where('shop_id', $shopId)->first();

        if (!$shop) {
            throw new \Exception("Shop {$shopId} không tồn tại");
        }

        $token = $this->tokenService->getValidToken($shopId);
        if (!$token) {
            throw new \Exception("Không có token hợp lệ cho shop {$shopId}");
        }

        $params = [
            'shop_cipher' => $shop->seller_cipher,
            'page_size' => 100,
            'app_key' => config('services.tiktok_shop.client_key'),
            'timestamp' => time(),
        ];

//        if ($pageToken) {
//            $params['page_token'] = $pageToken;
//        }

        // Generate signature using tts_sign method
        $body = json_encode([
            'status' => 'ALL',
            'category_version' => 'v1',
            'listing_platforms' => ['TIKTOK_SHOP'],
            'audit_status' => ['AUDIT_APPROVED', 'AUDITING', 'AUDIT_REJECTED'],
        ]);

        $signatureData = $this->tokenService->tts_sign(
            $params,
            '/product/202502/products/search',
            $body,
            'application/json',
            config('services.tiktok_shop.client_secret')
        );
        
        $url = 'https://open-api.tiktokglobalshop.com/product/202502/products/search?' . http_build_query($signatureData['signed_query']);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-tts-access-token' => $token->access_token,
        ])->withBody($body, 'application/json')
        ->post($url);

        if (!$response->successful()) {
            throw new \Exception('API request failed: ' . $response->body());
        }

        $data = $response->json();
        if (!$data || !isset($data['code']) || $data['code'] !== 0) {
            throw new \Exception('API returned error: ' . json_encode($data));
        }

        return $data;
    }

    /**
     * Process và lưu products vào database
     */
    private function processProducts(array $products, string $shopId): int
    {
        $synced = 0;

        foreach ($products as $productData) {
            try {
                $this->createOrUpdateProduct($productData, $shopId);
                $synced++;
            } catch (\Throwable $e) {
                Log::error('Failed to process product', [
                    'shop_id' => $shopId,
                    'product_id' => $productData['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $synced;
    }

    /**
     * Create hoặc update product trong database
     */
    private function createOrUpdateProduct(array $productData, string $shopId): void
    {
        $tiktokProductId = $productData['id'] ?? null;
        if (!$tiktokProductId) {
            throw new \Exception('Product ID không tồn tại');
        }

        // Calculate price range
        $minPrice = null;
        $maxPrice = null;
        $totalStock = 0;

        if (isset($productData['skus']) && is_array($productData['skus'])) {
            $prices = [];
            foreach ($productData['skus'] as $sku) {
                if (isset($sku['price']['tax_exclusive_price'])) {
                    $price = (float) $sku['price']['tax_exclusive_price'];
                    $prices[] = $price;
                }
                if (isset($sku['inventory']['available_stock'])) {
                    $totalStock += (int) $sku['inventory']['available_stock'];
                }
            }

            if (!empty($prices)) {
                $minPrice = min($prices);
                $maxPrice = max($prices);
            }
        }

        // Extract images
        $images = [];
        if (isset($productData['images']) && is_array($productData['images'])) {
            foreach ($productData['images'] as $image) {
                $images[] = [
                    'url' => $image['url'] ?? $image['image_url'] ?? null,
                    'alt' => $image['alt'] ?? null,
                ];
            }
        }

        Product::updateOrCreate(
            ['tiktok_product_id' => $tiktokProductId],
            [
                'shop_id' => $shopId,
                'title' => $productData['title'] ?? 'N/A',
                'description' => $productData['description'] ?? null,
                'status' => $productData['status'] ?? 'UNKNOWN',
                'images' => $images,
                'skus' => $productData['skus'] ?? [],
                'categories' => $productData['categories'] ?? [],
                'attributes' => $productData['attributes'] ?? [],
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'total_stock' => $totalStock,
                'tiktok_created_at' => isset($productData['create_time'])
                    ? date('Y-m-d H:i:s', $productData['create_time'])
                    : null,
                'tiktok_updated_at' => isset($productData['update_time'])
                    ? date('Y-m-d H:i:s', $productData['update_time'])
                    : null,
                'last_synced_at' => now(),
            ]
        );
    }
}
