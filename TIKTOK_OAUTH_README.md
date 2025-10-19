# TikTok Shop OAuth 2.0 Integration

## 🚀 Đã cập nhật theo OAuth 2.0 chuẩn

Hệ thống đã được cập nhật để sử dụng đúng OAuth 2.0 flow theo tài liệu chính thức của TikTok Partner Platform.

## 📋 Các thay đổi chính:

### 1. **Authorization Endpoint**
- **Cũ**: `https://services.tiktokshop.com/open/authorize`
- **Mới**: `https://auth.tiktok-shops.com/oauth/authorize`

### 2. **Token Exchange**
- **Cũ**: GET `/api/v2/token/get`
- **Mới**: POST `/oauth/token`

### 3. **OAuth 2.0 Parameters**
```php
$params = [
    'app_key' => $this->appKey,
    'redirect_uri' => $redirectUri,
    'state' => $state,
    'scope' => 'read_orders,read_products,read_returns,read_affiliate_orders',
    'response_type' => 'code',
];
```

### 4. **Token Response Structure**
```json
{
    "access_token": "xxx",
    "token_type": "Bearer",
    "expires_in": 3600,
    "refresh_token": "xxx",
    "scope": "read_orders,read_products,read_returns,read_affiliate_orders"
}
```

## 🔧 Cấu hình cần thiết:

### 1. **Environment Variables**
```bash
APP_URL=http://localhost:8000
TIKTOK_APP_KEY=6hdt7546b0rif
TIKTOK_APP_SECRET=2871879d3e179f0832cb59a3e8d307589b2f027c
```

### 2. **Redirect URI trong TikTok Developer Console**
```
http://localhost:8000/api/user-auth/tiktok/callback
```

## 🎯 Flow ủy quyền mới:

### **Bước 1: Tạo Authorization URL**
```
GET /api/user-auth/tiktok/authorize?organization_id=123&shop_name=MyShop
```
→ Redirect đến: `https://auth.tiktok-shops.com/oauth/authorize?...`

### **Bước 2: User ủy quyền trên TikTok**
- User login TikTok Shop account
- User grant permissions cho app
- TikTok redirect về callback với `authorization_code`

### **Bước 3: Exchange Token**
```
POST /api/user-auth/tiktok/callback
```
→ Đổi `authorization_code` lấy `access_token`

### **Bước 4: Sử dụng API**
- Access token được lưu trong session
- Có thể refresh token khi cần
- Gọi TikTok Shop API với Bearer token

## 🆕 Tính năng mới:

### 1. **Token Management Dashboard**
- Hiển thị thông tin token (type, expires_in, scope)
- Nút refresh token
- Theo dõi thời gian refresh

### 2. **Refresh Token Support**
```javascript
// Refresh token
fetch('/api/user-auth/tiktok/refresh-token', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    }
});
```

### 3. **Enhanced Error Handling**
- OAuth 2.0 error responses
- Detailed error messages
- Proper HTTP status codes

## 🔐 Bảo mật:

1. **State Parameter**: Ngăn chặn CSRF attacks
2. **CSRF Protection**: Laravel CSRF tokens
3. **HTTPS Only**: Tất cả API calls qua HTTPS
4. **Token Encryption**: Access tokens được mã hóa khi lưu
5. **Scope Validation**: Chỉ request permissions cần thiết

## 📊 API Endpoints:

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/user-auth/tiktok/authorize` | Tạo authorization URL |
| GET | `/api/user-auth/tiktok/callback` | Xử lý OAuth callback |
| GET | `/api/user-auth/tiktok/shop-info` | Lấy thông tin shop |
| POST | `/api/user-auth/tiktok/refresh-token` | Refresh access token |
| GET | `/api/user-auth/tiktok/test-orders` | Test API connection |

## 🚀 Cách sử dụng:

1. **Truy cập dashboard**: `http://localhost:8000/dashboard`
2. **Nhập thông tin** (tùy chọn):
   - Organization ID
   - Shop Name
3. **Click "Authorize TikTok Shop"**
4. **Login và grant permissions** trên TikTok
5. **Sử dụng các tính năng**:
   - Xem thông tin shop
   - Quản lý token
   - Test API connection

## ⚠️ Lưu ý quan trọng:

1. **Redirect URI** phải match với URL đã đăng ký trong TikTok Developer Console
2. **App Key và App Secret** phải chính xác
3. **HTTPS** được khuyến nghị cho production
4. **Token expiration** cần được xử lý tự động
5. **Rate limiting** cần được implement cho production

## 🔍 Debugging:

### 1. **Check Logs**
```bash
tail -f storage/logs/laravel.log
```

### 2. **Test Authorization URL**
```bash
curl "http://localhost:8000/api/user-auth/tiktok/authorize"
```

### 3. **Check Session Data**
```php
dd(session('tiktok_shop_data'));
```

## 📚 Tài liệu tham khảo:

- [TikTok Shop Partner Center](https://partner.tiktokshop.com/)
- [OAuth 2.0 RFC](https://tools.ietf.org/html/rfc6749)
- [Laravel HTTP Client](https://laravel.com/docs/http-client)
