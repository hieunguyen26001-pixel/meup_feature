<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],


    // ... các service khác ...

    // (tuỳ app bạn có dùng TikTok Login Kit hay không)
    'tiktok' => [
        'app_key' => env('TIKTOK_APP_KEY', '6hdt7546b0rif'),
        'app_secret' => env('TIKTOK_APP_SECRET', '2871879d3e179f0832cb59a3e8d307589b2f027c'),
    ],

    // TikTok Shop Partner (chuẩn hoá tên key theo code hiện tại)
    'tiktok_shop' => [
        'service_id' => '7546474000873817864',
        // Khóa ứng dụng (ưu tiên TIKTOK_*; fallback TT_SHOP_*)
        'client_key' => env('TIKTOK_APP_KEY', env('TT_SHOP_CLIENT_KEY')),
        'client_secret' => env('TIKTOK_APP_SECRET', env('TT_SHOP_CLIENT_SECRET')),

        // OAuth
        'redirect_uri' => env('TT_SHOP_REDIRECT_URI', 'http://localhost:8000/oauth/shop/callback'),
        'authorize_base' => env('TIKTOK_AUTHORIZE_BASE', env('TT_SHOP_AUTHORIZE_BASE', 'https://services.tiktokshop.com/open/authorize')),
        'auth_base' => env('TIKTOK_AUTH_BASE', env('TT_SHOP_AUTH_BASE', 'https://auth.tiktok-shops.com')),

        // Open API base (host) + path phiên bản API sản phẩm
        'open_api_base' => env('TIKTOK_OPEN_API_BASE', env('TT_SHOP_API_BASE', 'https://open-api.tiktokglobalshop.com')),
        // giữ api_base cho tương thích ngược (nếu chỗ nào cũ còn gọi api_base)
        'api_base' => env('TIKTOK_OPEN_API_BASE', env('TT_SHOP_API_BASE', 'https://open-api.tiktokglobalshop.com')),

        // Path API sản phẩm (đồng bộ với phần ký)
        // Nếu workspace của bạn chỉ có 202309, đổi về '/product/202309/products/search'
        'product_api_path' => env('TIKTOK_PRODUCT_API_PATH', '/product/202502/products/search'),

        // Scopes yêu cầu tối thiểu (đọc từ ENV dạng CSV, ví dụ: "shop.info.read,orders.read,products.read")
        'required_scopes' => array_values(array_filter(array_map('trim', explode(',', env(
            'TIKTOK_REQUIRED_SCOPES',
            // mặc định an toàn
            'shop.info.read,orders.read,products.read,returns.read'
        ))))),

        // Làm mới token trước hạn (giây)
        'refresh_ahead' => (int)env('TIKTOK_REFRESH_AHEAD', env('TOKEN_REFRESH_AHEAD', 600)),
    ],

];
