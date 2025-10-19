# TikTok Shop Lives Analytics API

## Tổng quan

API này cung cấp các endpoint để phân tích hiệu suất live stream của TikTok Shop, bao gồm:

- **Lives Performance** - Phân tích chi tiết hiệu suất live stream
- **Lives Summary** - Thống kê tổng quan về live stream
- **Top Lives** - Danh sách live stream có hiệu suất cao nhất
- **Lives Overview** - Tổng quan hiệu suất live stream với dữ liệu so sánh

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
- scopes: ['live.read', 'analytics.read']

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

### 1. Lives Performance API

#### Lấy danh sách lives performance
```bash
GET /api/analytics/shop/lives/performance?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd
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
    "live_stream_sessions": [
      {
        "live_id": "75xxxxxxxxxxxxxxx28",
        "title": "Live Stream Title",
        "username": "rey20195",
        "duration": {
          "seconds": 200,
          "formatted": "3m 20s",
          "start_time": {
            "timestamp": 1623812664,
            "formatted": "2021-06-16 10:04:24"
          },
          "end_time": {
            "timestamp": 1623812864,
            "formatted": "2021-06-16 10:07:44"
          }
        },
        "sales_performance": {
          "gmv": {
            "amount": 99.00,
            "currency": "USD",
            "formatted": "$99.00"
          },
          "products_added": {
            "count": 12,
            "formatted": "12"
          },
          "different_products_sold": {
            "count": 5,
            "formatted": "5"
          },
          "created_sku_orders": {
            "count": 100,
            "formatted": "100"
          },
          "sku_orders": {
            "count": 80,
            "formatted": "80"
          },
          "units_sold": {
            "count": 122,
            "formatted": "122"
          },
          "customers": {
            "count": 50,
            "formatted": "50"
          },
          "avg_price": {
            "amount": 9.00,
            "currency": "USD",
            "formatted": "$9.00"
          },
          "click_to_order_rate": {
            "rate": 18.0,
            "formatted": "18%"
          },
          "24h_live_gmv": {
            "amount": 340.00,
            "currency": "USD",
            "formatted": "$340.00"
          }
        },
        "interaction_performance": {
          "acu": {
            "count": 123,
            "formatted": "123"
          },
          "pcu": {
            "count": 1332,
            "formatted": "1,332"
          },
          "viewers": {
            "count": 18323,
            "formatted": "18,323"
          },
          "views": {
            "count": 112993,
            "formatted": "112,993"
          },
          "avg_viewing_duration": {
            "seconds": 46,
            "formatted": "46s"
          },
          "comments": {
            "count": 534,
            "formatted": "534"
          },
          "shares": {
            "count": 156,
            "formatted": "156"
          },
          "likes": {
            "count": 2442,
            "formatted": "2,442"
          },
          "new_followers": {
            "count": 12,
            "formatted": "12"
          },
          "product_impressions": {
            "count": 12,
            "formatted": "12"
          },
          "product_clicks": {
            "count": 3882,
            "formatted": "3,882"
          },
          "click_through_rate": {
            "rate": 13.99,
            "formatted": "13.99%"
          }
        },
        "performance_metrics": {
          "gmv_per_view": {
            "amount": 0.0009,
            "currency": "USD",
            "formatted": "$0.00"
          },
          "conversion_rate": 0.04,
          "order_rate": 0.07,
          "engagement_score": 2.77,
          "sales_efficiency": 12.5
        }
      }
    ],
    "pagination": {
      "next_page_token": "cGFnZV9udW1iZXI9MQ==",
      "total_count": 233,
      "page_size": 30,
      "has_more": true
    },
    "date_range": {
      "start_date": "2024-09-01",
      "end_date": "2024-09-08",
      "latest_available_date": "2024-09-07"
    },
    "filters": {
      "sort_field": "gmv",
      "sort_order": "ASC",
      "currency": "USD",
      "account_type": "OFFICIAL_ACCOUNTS"
    },
    "shop_id": "7496239622529452872",
    "source": "tiktok_api"
  }
}
```

### 2. Lives Summary API

#### Lấy thống kê tổng quan lives
```bash
GET /api/analytics/shop/lives/summary?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd
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
        "amount": 50000.00,
        "currency": "USD",
        "formatted": "$50,000.00"
      },
      "total_views": 500000,
      "total_customers": 2500,
      "total_orders": 2000,
      "total_units_sold": 3000,
      "total_engagement": 15000,
      "average_gmv_per_live": {
        "amount": 2500.00,
        "currency": "USD",
        "formatted": "$2,500.00"
      },
      "average_views_per_live": 25000.0,
      "average_customers_per_live": 125.0,
      "average_duration_per_live": {
        "seconds": 1800,
        "formatted": "30m 0s"
      },
      "total_live_streams": 20
    },
    "date_range": {
      "start_date": "2024-09-01",
      "end_date": "2024-09-08"
    },
    "total_live_streams": 20,
    "shop_id": "7496239622529452872",
    "source": "tiktok_api"
  }
}
```

### 3. Top Lives API

#### Lấy top lives theo GMV
```bash
GET /api/analytics/shop/lives/top?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&limit=10
```

**Parameters:**
- `start_date_ge` (required): Ngày bắt đầu (Y-m-d format)
- `end_date_lt` (required): Ngày kết thúc (Y-m-d format)
- `shop_cipher` (required): Shop cipher từ TikTok Shop
- `app_key` (required): App key từ TikTok Shop
- `limit` (optional): Số live top (max: 50, default: 10)

#### Response Example
```json
{
  "success": true,
  "data": {
    "top_lives": [
      {
        "live_id": "75xxxxxxxxxxxxxxx28",
        "title": "Top Performing Live",
        "username": "rey20195",
        "sales_performance": {
          "gmv": {
            "amount": 10000.00,
            "currency": "USD",
            "formatted": "$10,000.00"
          }
        },
        "interaction_performance": {
          "views": {
            "count": 100000,
            "formatted": "100,000"
          }
        },
        "rank": 1
      }
    ],
    "date_range": {
      "start_date": "2024-09-01",
      "end_date": "2024-09-08"
    },
    "total_found": 20,
    "shop_id": "7496239622529452872",
    "source": "tiktok_api"
  }
}
```

### 4. Lives Overview API

#### Lấy tổng quan hiệu suất lives với so sánh
```bash
GET /api/analytics/shop/lives/overview?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&with_comparison=true&granularity=1D
```

**Parameters:**
- `start_date_ge` (required): Ngày bắt đầu (Y-m-d format)
- `end_date_lt` (required): Ngày kết thúc (Y-m-d format)
- `shop_cipher` (required): Shop cipher từ TikTok Shop
- `app_key` (required): App key từ TikTok Shop
- `currency` (optional): Mã tiền tệ (default: USD)
- `account_type` (optional): Loại tài khoản (default: ALL)
- `with_comparison` (optional): Bao gồm dữ liệu so sánh (default: true)
- `granularity` (optional): Độ chi tiết dữ liệu (default: 1D)
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
          "amount": 11.00,
          "currency": "USD",
          "formatted": "$11.00"
        },
        "sku_orders": {
          "count": 20,
          "formatted": "20"
        },
        "customers": {
          "count": 4,
          "formatted": "4"
        },
        "units_sold": {
          "count": 2,
          "formatted": "2"
        },
        "click_to_order_rate": {
          "rate": 32.0,
          "formatted": "32%"
        },
        "click_through_rate": {
          "rate": 10.0,
          "formatted": "10%"
        },
        "performance_metrics": {
          "average_order_value": {
            "amount": 0.55,
            "currency": "USD",
            "formatted": "$0.55"
          },
          "units_per_order": 0.1,
          "orders_per_customer": 5.0,
          "gmv_per_customer": {
            "amount": 2.75,
            "currency": "USD",
            "formatted": "$2.75"
          },
          "conversion_efficiency": 12.0
        }
      }
    ],
    "comparison_data": [
      {
        "start_date": "2024-08-24",
        "end_date": "2024-08-31",
        "gmv": {
          "amount": 11.00,
          "currency": "USD",
          "formatted": "$11.00"
        },
        "sku_orders": {
          "count": 20,
          "formatted": "20"
        },
        "customers": {
          "count": 4,
          "formatted": "4"
        },
        "units_sold": {
          "count": 2,
          "formatted": "2"
        },
        "click_to_order_rate": {
          "rate": 32.0,
          "formatted": "32%"
        },
        "click_through_rate": {
          "rate": 10.0,
          "formatted": "10%"
        },
        "performance_metrics": {
          "average_order_value": {
            "amount": 0.55,
            "currency": "USD",
            "formatted": "$0.55"
          },
          "units_per_order": 0.1,
          "orders_per_customer": 5.0,
          "gmv_per_customer": {
            "amount": 2.75,
            "currency": "USD",
            "formatted": "$2.75"
          },
          "conversion_efficiency": 12.0
        }
      }
    ],
    "date_range": {
      "start_date": "2024-09-01",
      "end_date": "2024-09-08",
      "latest_available_date": "2024-09-07"
    },
    "filters": {
      "currency": "USD",
      "account_type": "ALL",
      "with_comparison": true,
      "granularity": "1D"
    },
    "shop_id": "7496239622529452872",
    "source": "tiktok_api"
  }
}`
```

## Cấu trúc dữ liệu

### Live Stream Session Object
- `live_id`: ID live stream
- `title`: Tiêu đề live stream
- `username`: Tên người dùng
- `duration`: Thông tin thời lượng (seconds, formatted, start_time, end_time)
- `sales_performance`: Hiệu suất bán hàng
- `interaction_performance`: Hiệu suất tương tác
- `performance_metrics`: Các chỉ số hiệu suất

### Sales Performance Object
- `gmv`: Thông tin GMV (amount, currency, formatted)
- `products_added`: Số sản phẩm đã thêm
- `different_products_sold`: Số sản phẩm khác nhau đã bán
- `created_sku_orders`: Số đơn hàng SKU đã tạo
- `sku_orders`: Số đơn hàng SKU
- `units_sold`: Số lượng đã bán
- `customers`: Số khách hàng
- `avg_price`: Giá trung bình
- `click_to_order_rate`: Tỷ lệ click to order
- `24h_live_gmv`: GMV 24h của live

### Interaction Performance Object
- `acu`: Average Concurrent Users
- `pcu`: Peak Concurrent Users
- `viewers`: Số người xem
- `views`: Số lượt xem
- `avg_viewing_duration`: Thời gian xem trung bình
- `comments`: Số bình luận
- `shares`: Số chia sẻ
- `likes`: Số lượt thích
- `new_followers`: Số follower mới
- `product_impressions`: Số lần hiển thị sản phẩm
- `product_clicks`: Số click sản phẩm
- `click_through_rate`: Tỷ lệ click through

### Performance Metrics
- `gmv_per_view`: GMV mỗi lượt xem
- `conversion_rate`: Tỷ lệ chuyển đổi
- `order_rate`: Tỷ lệ đặt hàng
- `engagement_score`: Điểm tương tác (0-100)
- `sales_efficiency`: Điểm hiệu quả bán hàng (0-100)

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
# Test lives performance
curl -X GET "http://localhost:8000/api/analytics/shop/lives/performance?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&page_size=30&sort_field=gmv&sort_order=ASC" -H "Accept: application/json"

# Test lives summary
curl -X GET "http://localhost:8000/api/analytics/shop/lives/summary?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd" -H "Accept: application/json"

# Test top lives
curl -X GET "http://localhost:8000/api/analytics/shop/lives/top?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&limit=10" -H "Accept: application/json"

# Test lives overview
curl -X GET "http://localhost:8000/api/analytics/shop/lives/overview?start_date_ge=2024-09-01&end_date_lt=2024-09-08&shop_cipher=GCP_XF90igAAAABh00qsWgtvOiGFNqyubMt3&app_key=38abcd&with_comparison=true&granularity=1D" -H "Accept: application/json"
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
- Likes rate (40%)
- Comments rate (30%)
- Shares rate (30%)

### 2. Sales Efficiency Score
Điểm hiệu quả bán hàng được tính dựa trên:
- GMV (40%)
- Customers (30%)
- CTR (20%)
- Views (10%)

### 3. Duration Formatting
Hỗ trợ format thời gian:
- Giây: "46s"
- Phút: "3m 20s"
- Giờ: "1h 30m 0s"

### 4. Performance Metrics
Tự động tính toán các chỉ số:
- GMV per View
- Conversion Rate
- Order Rate
- Engagement Score
- Sales Efficiency

---

**Happy Live Stream Analytics! 🎥📊**
