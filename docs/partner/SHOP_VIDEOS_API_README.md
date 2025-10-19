# TikTok Shop Videos Analytics API

## Tổng quan

API này cung cấp các endpoint để phân tích hiệu suất video của TikTok Shop, bao gồm:

- **Videos Performance** - Phân tích chi tiết hiệu suất video
- **Videos Summary** - Thống kê tổng quan về video
- **Top Videos** - Danh sách video có hiệu suất cao nhất
- **Videos By Product** - Video liên quan đến sản phẩm cụ thể
- **Videos Overview** - Tổng quan hiệu suất video với dữ liệu so sánh
- **Video Performance By ID** - Hiệu suất chi tiết của video cụ thể theo ID

## Yêu cầu trước khi sử dụng

### 1. Cấu hình Database
Đảm bảo database có dữ liệu shop và token hợp lệ:

```sql
-- Bảng shops
shops:
- shop_id: ID shop từ TikTok Shop
- shop_name: Tên shop
- seller_cipher: Mã cipher thực từ TikTok Shop (QUAN TRỌNG!)
- is_active: true
- scopes: ['video.read', 'analytics.read']

-- Bảng provider_tokens  
provider_tokens:
- provider: 'SHOP'
- subject_id: shop_id tương ứng
- access_token: Token thực từ TikTok Shop
- refresh_token: Refresh token
- expires_at: Thời gian hết hạn
```

### 2. Lấy shop_cipher và app_key thực
**QUAN TRỌNG**: Các thông tin này phải là giá trị thực từ TikTok Shop, không phải dữ liệu test.

## API Endpoints

### 1. Videos Performance API

#### Lấy danh sách videos performance
```bash
GET /api/analytics/shop/videos/performance?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd
```

**Parameters:**
- `start_date_ge` (required): Ngày bắt đầu (Y-m-d format)
- `end_date_lt` (required): Ngày kết thúc (Y-m-d format)
- `shop_cipher` (required): Shop cipher từ TikTok Shop
- `app_key` (required): App key từ TikTok Shop
- `page_size` (optional): Số records mỗi trang (default: 10)
- `page_token` (optional): Token phân trang
- `sort_field` (optional): Trường sắp xếp (default: gmv)
- `sort_order` (optional): Thứ tự ASC/DESC (default: DESC)
- `currency` (optional): Mã tiền tệ (default: USD)
- `account_type` (optional): Loại tài khoản (default: ALL)
- `timestamp` (optional): Timestamp của request

#### Response Example
```json
{
  "success": true,
  "data": {
    "videos_performance": [
      {
        "video_id": "172xxxxxxxxxxxxx089",
        "title": "Video Title",
        "username": "Video Username",
        "gmv": {
          "amount": 1000.00,
          "currency": "USD",
          "formatted": "$1,000.00"
        },
        "orders": {
          "count": 10,
          "formatted": "10"
        },
        "units_sold": {
          "count": 10,
          "formatted": "10"
        },
        "views": {
          "count": 5000,
          "formatted": "5,000"
        },
        "click_through_rate": {
          "rate": 2.5,
          "formatted": "2.50%"
        },
        "products": [
          {
            "product_id": "105xxxxxxxxxxxxx247",
            "name": "Product Name",
            "display_name": "Product Name"
          }
        ],
        "video_post_time": "2025-01-01 00:00:00",
        "video_post_date": {
          "raw": "2025-01-01 00:00:00",
          "formatted": "Jan 01, 2025 00:00",
          "relative": "2 days ago",
          "timestamp": 1735689600
        },
        "performance_metrics": {
          "average_order_value": {
            "amount": 100.00,
            "currency": "USD",
            "formatted": "$100.00"
          },
          "conversion_rate": 0.2,
          "units_per_order": 1.0,
          "revenue_per_view": {
            "amount": 0.2,
            "currency": "USD",
            "formatted": "$0.20"
          },
          "engagement_score": 45.5
        }
      }
    ],
    "pagination": {
      "next_page_token": "cGFnZV9udW1iZXI9MQ==",
      "total_count": 10,
      "page_size": 10,
      "has_more": true
    },
    "date_range": {
      "start_date": "2024-09-01",
      "end_date": "2024-09-08",
      "latest_available_date": "2024-09-07"
    },
    "filters": {
      "sort_field": "gmv",
      "sort_order": "DESC",
      "currency": "USD",
      "account_type": "ALL"
    },
    "shop_id": "7496239622529452872",
    "source": "tiktok_api"
  }
}
```

### 2. Videos Summary API

#### Lấy thống kê tổng quan videos
```bash
GET /api/analytics/shop/videos/summary?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd
```

**Parameters:**
- `start_date_ge` (required): Ngày bắt đầu (Y-m-d format)
- `end_date_lt` (required): Ngày kết thúc (Y-m-d format)
- `shop_cipher` (required): Shop cipher từ TikTok Shop
- `app_key` (required): App key từ TikTok Shop

#### Response Example
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_gmv": {
        "amount": 25000.00,
        "currency": "USD",
        "formatted": "$25,000.00"
      },
      "total_orders": 250,
      "total_units_sold": 250,
      "total_views": 125000,
      "average_gmv_per_video": {
        "amount": 2500.00,
        "currency": "USD",
        "formatted": "$2,500.00"
      },
      "average_orders_per_video": 25.0,
      "average_views_per_video": 12500.0,
      "average_ctr": 2.5,
      "total_videos": 10
    },
    "date_range": {
      "start_date": "2024-09-01",
      "end_date": "2024-09-08"
    },
    "total_videos": 10,
    "shop_id": "7496239622529452872",
    "source": "tiktok_api"
  }
}
```

### 3. Top Videos API

#### Lấy top videos theo GMV
```bash
GET /api/analytics/shop/videos/top?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&limit=10
```

**Parameters:**
- `start_date_ge` (required): Ngày bắt đầu (Y-m-d format)
- `end_date_lt` (required): Ngày kết thúc (Y-m-d format)
- `shop_cipher` (required): Shop cipher từ TikTok Shop
- `app_key` (required): App key từ TikTok Shop
- `limit` (optional): Số video top (max: 50, default: 10)

#### Response Example
```json
{
  "success": true,
  "data": {
    "top_videos": [
      {
        "video_id": "172xxxxxxxxxxxxx089",
        "title": "Top Performing Video",
        "username": "Video Username",
        "gmv": {
          "amount": 5000.00,
          "currency": "USD",
          "formatted": "$5,000.00"
        },
        "orders": {
          "count": 50,
          "formatted": "50"
        },
        "units_sold": {
          "count": 50,
          "formatted": "50"
        },
        "views": {
          "count": 25000,
          "formatted": "25,000"
        },
        "rank": 1
      }
    ],
    "date_range": {
      "start_date": "2024-09-01",
      "end_date": "2024-09-08"
    },
    "total_found": 10,
    "shop_id": "7496239622529452872",
    "source": "tiktok_api"
  }
}
```

### 4. Videos By Product API

#### Lấy videos theo sản phẩm
```bash
GET /api/analytics/shop/videos/by-product?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&product_id=105xxxxxxxxxxxxx247
```

**Parameters:**
- `start_date_ge` (required): Ngày bắt đầu (Y-m-d format)
- `end_date_lt` (required): Ngày kết thúc (Y-m-d format)
- `shop_cipher` (required): Shop cipher từ TikTok Shop
- `app_key` (required): App key từ TikTok Shop
- `product_id` (required): ID sản phẩm cần lọc

#### Response Example
```json
{
  "success": true,
  "data": {
    "videos": [
      {
        "video_id": "172xxxxxxxxxxxxx089",
        "title": "Video featuring Product",
        "username": "Video Username",
        "gmv": {
          "amount": 2000.00,
          "currency": "USD",
          "formatted": "$2,000.00"
        },
        "products": [
          {
            "product_id": "105xxxxxxxxxxxxx247",
            "name": "Product Name",
            "display_name": "Product Name"
          }
        ]
      }
    ],
    "product_id": "105xxxxxxxxxxxxx247",
    "date_range": {
      "start_date": "2024-09-01",
      "end_date": "2024-09-08"
    },
    "total_found": 3,
    "shop_id": "7496239622529452872",
    "source": "tiktok_api"
  }
}
```

### 5. Videos Overview API

#### Lấy tổng quan hiệu suất video với so sánh
```bash
GET /api/analytics/shop/videos/overview?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&with_comparison=true&granularity=ALL
```

**Parameters:**
- `start_date_ge` (required): Ngày bắt đầu (Y-m-d format)
- `end_date_lt` (required): Ngày kết thúc (Y-m-d format)
- `shop_cipher` (required): Shop cipher từ TikTok Shop
- `app_key` (required): App key từ TikTok Shop
- `currency` (optional): Mã tiền tệ (default: USD)
- `account_type` (optional): Loại tài khoản (default: ALL)
- `with_comparison` (optional): Bao gồm dữ liệu so sánh (default: true)
- `granularity` (optional): Độ chi tiết dữ liệu (default: ALL)
- `timestamp` (optional): Timestamp của request

#### Response Example
```json
{
  "success": true,
  "data": {
    "overview_performance": [
      {
        "start_date": "2024-09-01",
        "end_date": "2024-09-08",
        "gmv": {
          "amount": 50000.00,
          "currency": "USD",
          "formatted": "$50,000.00"
        },
        "click_through_rate": {
          "rate": 2.5,
          "formatted": "2.50%"
        },
        "orders": {
          "count": 500,
          "formatted": "500"
        },
        "units_sold": {
          "count": 500,
          "formatted": "500"
        },
        "performance_metrics": {
          "average_order_value": {
            "amount": 100.00,
            "currency": "USD",
            "formatted": "$100.00"
          },
          "units_per_order": 1.0,
          "gmv_per_unit": {
            "amount": 100.00,
            "currency": "USD",
            "formatted": "$100.00"
          },
          "conversion_efficiency": 85.5
        }
      }
    ],
    "comparison_data": [
      {
        "start_date": "2024-08-25",
        "end_date": "2024-09-01",
        "gmv": {
          "amount": 45000.00,
          "currency": "USD",
          "formatted": "$45,000.00"
        },
        "click_through_rate": {
          "rate": 2.2,
          "formatted": "2.20%"
        },
        "orders": {
          "count": 450,
          "formatted": "450"
        },
        "units_sold": {
          "count": 450,
          "formatted": "450"
        },
        "performance_metrics": {
          "average_order_value": {
            "amount": 100.00,
            "currency": "USD",
            "formatted": "$100.00"
          },
          "units_per_order": 1.0,
          "gmv_per_unit": {
            "amount": 100.00,
            "currency": "USD",
            "formatted": "$100.00"
          },
          "conversion_efficiency": 82.0
        }
      }
    ],
    "date_range": {
      "start_date": "2024-09-01",
      "end_date": "2024-09-08",
      "latest_available_date": "2024-09-08"
    },
    "filters": {
      "currency": "USD",
      "account_type": "ALL",
      "with_comparison": true,
      "granularity": "ALL"
    },
    "shop_id": "7496239622529452872",
    "source": "tiktok_api"
  }
}`
```

## Cấu trúc dữ liệu

### Video Performance Object
- `video_id`: ID video
- `title`: Tiêu đề video
- `username`: Tên người dùng
- `gmv`: Thông tin GMV (amount, currency, formatted)
- `orders`: Thông tin đơn hàng (count, formatted)
- `units_sold`: Số lượng bán (count, formatted)
- `views`: Số lượt xem (count, formatted)
- `click_through_rate`: Tỷ lệ click (rate, formatted)
- `products`: Danh sách sản phẩm trong video
- `video_post_time`: Thời gian đăng video
- `video_post_date`: Thông tin ngày đăng (formatted, relative)
- `performance_metrics`: Các chỉ số hiệu suất

### Performance Metrics
- `average_order_value`: Giá trị đơn hàng trung bình
- `conversion_rate`: Tỷ lệ chuyển đổi
- `units_per_order`: Số lượng sản phẩm mỗi đơn hàng
- `revenue_per_view`: Doanh thu mỗi lượt xem
- `engagement_score`: Điểm tương tác (0-100)

### Overview Performance Object
- `start_date`: Ngày bắt đầu khoảng thời gian
- `end_date`: Ngày kết thúc khoảng thời gian
- `gmv`: Thông tin GMV (amount, currency, formatted)
- `click_through_rate`: Tỷ lệ click (rate, formatted)
- `orders`: Thông tin đơn hàng (count, formatted)
- `units_sold`: Số lượng bán (count, formatted)
- `performance_metrics`: Các chỉ số hiệu suất

### Overview Performance Metrics
- `average_order_value`: Giá trị đơn hàng trung bình
- `units_per_order`: Số lượng sản phẩm mỗi đơn hàng
- `gmv_per_unit`: GMV mỗi đơn vị sản phẩm
- `conversion_efficiency`: Điểm hiệu quả chuyển đổi (0-100)

## Lỗi thường gặp

### 1. "Không có shop nào được ủy quyền"
**Nguyên nhân**: Không có shop nào trong database hoặc không có shop active
**Giải pháp**: Thêm shop vào database với `is_active = true`

### 2. "Shop cipher không hợp lệ"
**Nguyên nhân**: Shop_cipher là dữ liệu test, không phải từ TikTok Shop thực
**Giải pháp**: Lấy shop_cipher thực từ TikTok Shop Developer Console

### 3. "Không có token hợp lệ"
**Nguyên nhân**: Token hết hạn hoặc không tồn tại
**Giải pháp**: Refresh token hoặc ủy quyền lại shop

### 4. "Dữ liệu đầu vào không hợp lệ"
**Nguyên nhân**: Thiếu tham số bắt buộc hoặc format không đúng
**Giải pháp**: Kiểm tra các tham số required và format ngày tháng

## Test API

### Với curl
```bash
# Test videos performance
curl -X GET "http://localhost:8000/api/analytics/shop/videos/performance?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&page_size=5" -H "Accept: application/json"

# Test videos summary
curl -X GET "http://localhost:8000/api/analytics/shop/videos/summary?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd" -H "Accept: application/json"

# Test top videos
curl -X GET "http://localhost:8000/api/analytics/shop/videos/top?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&limit=5" -H "Accept: application/json"

# Test videos overview
curl -X GET "http://localhost:8000/api/analytics/shop/videos/overview?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&with_comparison=true&granularity=ALL" -H "Accept: application/json"
```

### Với API Explorer
Truy cập `http://localhost:5175/api-explorer` để test các endpoint với giao diện trực quan.

## Lưu ý quan trọng

1. **Shop_cipher và App_key phải thực**: Không sử dụng dữ liệu test
2. **Token phải hợp lệ**: Kiểm tra thời gian hết hạn
3. **Database phải có dữ liệu**: Shop và token phải tồn tại
4. **API rate limits**: TikTok Shop có giới hạn số request
5. **Error handling**: Luôn kiểm tra response success trước khi xử lý data
6. **Date format**: Sử dụng format Y-m-d cho ngày tháng
7. **Pagination**: Sử dụng page_token để lấy dữ liệu trang tiếp theo

## Tính năng nâng cao

### 1. Engagement Score
Điểm tương tác được tính dựa trên:
- Conversion rate (40%)
- Click-through rate (30%)
- GMV (30%, tối đa 30 điểm)

### 2. Performance Metrics
Tự động tính toán các chỉ số:
- Average Order Value (AOV)
- Conversion Rate
- Units per Order
- Revenue per View

### 3. Date Formatting
Hỗ trợ nhiều format ngày:
- Raw timestamp
- Formatted date (M d, Y H:i)
- Relative time (2 days ago)

### 4. Product Filtering
Lọc video theo sản phẩm cụ thể với thông tin chi tiết.

---

**Happy Video Analytics! 🎥📊**
