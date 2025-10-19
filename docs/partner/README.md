# TikTok Shop Partner APIs

## Tổng quan

Thư mục này chứa tất cả các API dành cho đối tác (Partner) của TikTok Shop, bao gồm các API analytics chi tiết cho SKUs, Videos và Live Streams.

## Cấu trúc thư mục

```
docs/partner/
├── README.md                           # File này
├── SHOP_SKUS_API_README.md            # API cho SKUs Analytics
├── SHOP_VIDEOS_API_README.md          # API cho Videos Analytics
└── SHOP_LIVES_API_README.md           # API cho Lives Analytics
```

## Danh sách API

### 1. Core APIs
- **Orders** - Quản lý đơn hàng (tạo, cập nhật, xem chi tiết)
- **Returns** - Quản lý trả hàng (tạo, cập nhật, xem chi tiết)
- **Cancellations** - Quản lý hủy đơn (tạo, cập nhật, xem chi tiết)
- **Products** - Quản lý sản phẩm (tạo, cập nhật, xem danh sách)
- **Authentication** - Xác thực TikTok Shop (OAuth flow)
- **Shop** - Thông tin shop (chi tiết shop, danh sách shop)
- **Shop Analytics** - Analytics cơ bản (tổng quan shop)

### 2. SKUs Analytics APIs
- **Performance** - Phân tích chi tiết hiệu suất SKUs
- **Summary** - Thống kê tổng quan về SKUs
- **Top SKUs** - Danh sách SKUs có hiệu suất cao nhất

**Documentation**: [SHOP_SKUS_API_README.md](./SHOP_SKUS_API_README.md)

### 3. Videos Analytics APIs
- **Performance** - Phân tích chi tiết hiệu suất video
- **Summary** - Thống kê tổng quan về video
- **Top Videos** - Danh sách video có hiệu suất cao nhất
- **Videos By Product** - Video liên quan đến sản phẩm cụ thể
- **Videos Overview** - Tổng quan hiệu suất video với dữ liệu so sánh
- **Video Performance By ID** - Hiệu suất chi tiết của video cụ thể theo ID

**Documentation**: [SHOP_VIDEOS_API_README.md](./SHOP_VIDEOS_API_README.md)

### 4. Lives Analytics APIs
- **Performance** - Phân tích chi tiết hiệu suất live stream
- **Summary** - Thống kê tổng quan về live stream
- **Top Lives** - Danh sách live stream có hiệu suất cao nhất
- **Lives Overview** - Tổng quan hiệu suất live stream với dữ liệu so sánh

**Documentation**: [SHOP_LIVES_API_README.md](./SHOP_LIVES_API_README.md)

## Cấu trúc Controller

Các controller được tổ chức trong thư mục `app/Http/Controllers/Api/Partner/`:

```
app/Http/Controllers/Api/Partner/
├── OrderApiController.php              # Controller cho Orders
├── ReturnApiController.php             # Controller cho Returns
├── CancellationApiController.php       # Controller cho Cancellations
├── ProductApiController.php            # Controller cho Products
├── TikTokAuthController.php            # Controller cho Authentication
├── ShopApiController.php               # Controller cho Shop
├── ShopAnalyticsController.php         # Controller cho Shop Analytics
├── ShopSkusAnalyticsController.php     # Controller cho SKUs Analytics
├── ShopVideosAnalyticsController.php   # Controller cho Videos Analytics
└── ShopLivesAnalyticsController.php    # Controller cho Lives Analytics
```

## Routes

Tất cả các API partner được định nghĩa trong `routes/api.php`:

### Core API Routes
- `GET /api/orders` - Danh sách đơn hàng
- `GET /api/orders/{id}` - Chi tiết đơn hàng
- `POST /api/orders` - Tạo đơn hàng mới
- `PUT /api/orders/{id}` - Cập nhật đơn hàng
- `GET /api/returns` - Danh sách trả hàng
- `GET /api/returns/{id}` - Chi tiết trả hàng
- `POST /api/returns` - Tạo trả hàng mới
- `GET /api/cancellations` - Danh sách hủy đơn
- `GET /api/cancellations/{id}` - Chi tiết hủy đơn
- `POST /api/cancellations` - Tạo hủy đơn mới
- `GET /api/products` - Danh sách sản phẩm
- `GET /api/products/{id}` - Chi tiết sản phẩm
- `POST /api/products` - Tạo sản phẩm mới
- `PUT /api/products/{id}` - Cập nhật sản phẩm
- `GET /api/shops` - Danh sách shop
- `GET /api/shops/{id}` - Chi tiết shop
- `GET /api/analytics/shop` - Analytics cơ bản

### Analytics API Routes (prefix `/api/analytics/shop/`)

#### SKUs Routes
- `GET /api/analytics/shop/skus/performance`
- `GET /api/analytics/shop/skus/summary`
- `GET /api/analytics/shop/skus/top`

#### Videos Routes
- `GET /api/analytics/shop/videos/performance`
- `GET /api/analytics/shop/videos/summary`
- `GET /api/analytics/shop/videos/top`
- `GET /api/analytics/shop/videos/by-product`
- `GET /api/analytics/shop/videos/overview`
- `GET /api/analytics/shop/videos/{videoId}/performance`

#### Lives Routes
- `GET /api/analytics/shop/lives/performance`
- `GET /api/analytics/shop/lives/summary`
- `GET /api/analytics/shop/lives/top`
- `GET /api/analytics/shop/lives/overview`

## Yêu cầu chung

### 1. Authentication
Tất cả API đều yêu cầu:
- `shop_cipher`: Mã cipher shop từ TikTok Shop
- `app_key`: App key từ TikTok Shop
- Access token hợp lệ trong database

### 2. Parameters chung
- `start_date_ge`: Ngày bắt đầu (Y-m-d format)
- `end_date_lt`: Ngày kết thúc (Y-m-d format)
- `currency`: Mã tiền tệ (default: USD)
- `timestamp`: Timestamp của request

### 3. Response Format
Tất cả API đều trả về format chuẩn:
```json
{
  "success": true,
  "data": {
    // Dữ liệu response
  }
}
```

## Testing

### API Explorer
Truy cập `http://localhost:5175/api-explorer` để test các endpoint với giao diện trực quan.

### Test Center
Truy cập `http://localhost:5175/api-test` để test các API với công cụ chuyên dụng.

## Lưu ý quan trọng

1. **Shop_cipher và App_key phải thực**: Không sử dụng dữ liệu test
2. **Token phải hợp lệ**: Kiểm tra thời gian hết hạn
3. **Database phải có dữ liệu**: Shop và token phải tồn tại
4. **API rate limits**: TikTok Shop có giới hạn số request
5. **Error handling**: Luôn kiểm tra response success trước khi xử lý data

## Tính năng nâng cao

### Data Transformation
- Format currency đa dạng (USD, VND, EUR, GBP)
- Format số với dấu phẩy
- Format thời gian (seconds, minutes, hours)
- Format tỷ lệ phần trăm

### Performance Metrics
- Engagement Score (0-100)
- Conversion Efficiency (0-100)
- Sales Efficiency (0-100)
- Performance Score (0-100)

### Advanced Features
- Pagination với next_page_token
- Sorting theo nhiều trường
- Filtering theo account_type
- Comparison data cho overview APIs
- Granularity support (1D, 7D, 30D)

---

**Happy Partner Analytics! 🚀📊**
