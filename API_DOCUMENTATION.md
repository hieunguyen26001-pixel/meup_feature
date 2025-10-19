# TikTok Shop API Documentation

## Tổng quan

Dự án này cung cấp một bộ API hoàn chỉnh để tích hợp với TikTok Shop, bao gồm các API cho đơn hàng, sản phẩm, shop và analytics chi tiết.

## Cấu trúc dự án

```
tiktok_lafit/
├── app/Http/Controllers/Api/
│   └── Partner/                          # Tất cả APIs dành cho đối tác
│       ├── OrderApiController.php        # API đơn hàng
│       ├── ReturnApiController.php       # API trả hàng
│       ├── CancellationApiController.php # API hủy đơn
│       ├── ProductApiController.php      # API sản phẩm
│       ├── TikTokAuthController.php      # API xác thực
│       ├── ShopApiController.php         # API shop
│       ├── ShopAnalyticsController.php   # API analytics cơ bản
│       ├── ShopSkusAnalyticsController.php    # API SKUs Analytics
│       ├── ShopVideosAnalyticsController.php  # API Videos Analytics
│       └── ShopLivesAnalyticsController.php   # API Lives Analytics
├── docs/
│   └── partner/                          # Documentation cho Partner APIs
│       ├── README.md
│       ├── SHOP_SKUS_API_README.md
│       ├── SHOP_VIDEOS_API_README.md
│       └── SHOP_LIVES_API_README.md
└── resources/js/
    ├── pages/
    │   ├── ApiTestPage.vue               # Trang test API
    │   └── ApiExplorerPage.vue           # Trang khám phá API
    └── components/
        └── ApiExplorer.vue               # Component khám phá API
```

## Danh sách API

### 1. Partner APIs
Tất cả các API được tổ chức trong thư mục `Partner/`:

#### Core APIs
- **Orders** - Quản lý đơn hàng
- **Returns** - Quản lý trả hàng
- **Cancellations** - Quản lý hủy đơn
- **Products** - Quản lý sản phẩm
- **Authentication** - Xác thực TikTok Shop
- **Shop** - Thông tin shop
- **Shop Analytics** - Analytics cơ bản

#### Advanced Analytics APIs
- **SKUs Analytics** - Performance, Summary, Top SKUs
- **Videos Analytics** - Performance, Summary, Top Videos, By Product, Overview, By ID
- **Lives Analytics** - Performance, Summary, Top Lives, Overview

**Chi tiết**: [Partner APIs Documentation](./docs/partner/README.md)

## Frontend Tools

### 1. API Test Center
- **URL**: `http://localhost:5175/api-test`
- **Mô tả**: Công cụ test API tương tự Postman
- **Tính năng**:
  - Build request với method, URL, headers, params, body
  - Execute request và xem response
  - Lưu lịch sử request
  - Export/Import history

### 2. API Explorer
- **URL**: `http://localhost:5175/api-explorer`
- **Mô tả**: Giao diện khám phá API tương tự Swagger UI
- **Tính năng**:
  - Danh sách tất cả API endpoints
  - Chi tiết parameters và response examples
  - One-click testing
  - Tìm kiếm và filter APIs

## Cài đặt và chạy

### 1. Backend (Laravel)
```bash
# Cài đặt dependencies
composer install

# Cấu hình database
cp .env.example .env
# Cập nhật DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Chạy migrations
php artisan migrate

# Chạy server
php artisan serve
```

### 2. Frontend (Vue.js)
```bash
# Cài đặt dependencies
npm install

# Chạy development server
npm run dev
```

## Cấu hình

### 1. Database
Đảm bảo có các bảng:
- `shops` - Thông tin shop
- `provider_tokens` - Token TikTok Shop
- `orders` - Đơn hàng
- `returns` - Trả hàng
- `cancellations` - Hủy đơn

### 2. TikTok Shop
Cấu hình trong `.env`:
```env
TIKTOK_SHOP_CLIENT_KEY=your_client_key
TIKTOK_SHOP_CLIENT_SECRET=your_client_secret
TIKTOK_SHOP_REDIRECT_URI=your_redirect_uri
```

## Testing

### 1. API Testing
- Sử dụng **API Test Center** cho test thủ công
- Sử dụng **API Explorer** cho khám phá API
- Sử dụng curl commands trong documentation

### 2. Example curl
```bash
# Test SKUs Performance
curl -X GET "http://localhost:8000/api/analytics/shop/skus/performance?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=YOUR_SHOP_CIPHER&app_key=YOUR_APP_KEY" -H "Accept: application/json"

# Test Videos Performance
curl -X GET "http://localhost:8000/api/analytics/shop/videos/performance?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=YOUR_SHOP_CIPHER&app_key=YOUR_APP_KEY" -H "Accept: application/json"

# Test Lives Performance
curl -X GET "http://localhost:8000/api/analytics/shop/lives/performance?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=YOUR_SHOP_CIPHER&app_key=YOUR_APP_KEY" -H "Accept: application/json"
```

## Tính năng nổi bật

### 1. Data Transformation
- Format currency đa dạng (USD, VND, EUR, GBP)
- Format số với dấu phẩy
- Format thời gian (seconds, minutes, hours)
- Format tỷ lệ phần trăm

### 2. Performance Metrics
- Engagement Score (0-100)
- Conversion Efficiency (0-100)
- Sales Efficiency (0-100)
- Performance Score (0-100)

### 3. Advanced Features
- Pagination với next_page_token
- Sorting theo nhiều trường
- Filtering theo account_type
- Comparison data cho overview APIs
- Granularity support (1D, 7D, 30D)

### 4. Error Handling
- Validation đầy đủ cho parameters
- Try-catch và logging chi tiết
- Error messages rõ ràng
- HTTP status codes chuẩn

## Lưu ý quan trọng

1. **Shop_cipher và App_key phải thực**: Không sử dụng dữ liệu test
2. **Token phải hợp lệ**: Kiểm tra thời gian hết hạn
3. **Database phải có dữ liệu**: Shop và token phải tồn tại
4. **API rate limits**: TikTok Shop có giới hạn số request
5. **Error handling**: Luôn kiểm tra response success trước khi xử lý data

## Hỗ trợ

- **Documentation**: Xem trong thư mục `docs/`
- **API Explorer**: `http://localhost:5175/api-explorer`
- **API Test Center**: `http://localhost:5175/api-test`

---

**Happy TikTok Shop Integration! 🚀📊**
