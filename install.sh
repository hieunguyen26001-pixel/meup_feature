#!/bin/bash

echo "🚀 Cài đặt Laravel 11 với Docker..."

# Kiểm tra Docker và Docker Compose
if ! command -v docker &> /dev/null; then
    echo "❌ Docker chưa được cài đặt. Vui lòng cài đặt Docker trước."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose chưa được cài đặt. Vui lòng cài đặt Docker Compose trước."
    exit 1
fi

# Tạo file .env từ .env.example
if [ ! -f .env ]; then
    echo "📝 Tạo file .env..."
    cat > .env << 'EOF'
APP_NAME="TikTok LaFit"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=tiktok_lafit
DB_USERNAME=tiktok_lafit
DB_PASSWORD=password

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
EOF
fi

# Build và khởi chạy containers
echo "🐳 Build và khởi chạy Docker containers..."
docker-compose up -d --build

# Chờ database khởi động
echo "⏳ Chờ database khởi động..."
sleep 30

# Cài đặt Laravel 11
echo "📦 Cài đặt Laravel 11..."
docker-compose exec app composer create-project laravel/laravel . --prefer-dist

# Cài đặt dependencies
echo "📚 Cài đặt dependencies..."
docker-compose exec app composer install

# Tạo application key
echo "🔑 Tạo application key..."
docker-compose exec app php artisan key:generate

# Chạy migrations
echo "🗄️ Chạy database migrations..."
docker-compose exec app php artisan migrate

# Cài đặt Node.js dependencies (nếu cần)
echo "📦 Cài đặt Node.js dependencies..."
docker-compose exec app npm install

# Build assets
echo "🎨 Build assets..."
docker-compose exec app npm run build

echo "✅ Cài đặt hoàn tất!"
echo ""
echo "🌐 Ứng dụng Laravel 11 đã sẵn sàng tại: http://localhost:8000"
echo "🗄️  phpMyAdmin tại: http://localhost:8080"
echo ""
echo "📋 Các lệnh hữu ích:"
echo "  - Khởi động: docker-compose up -d"
echo "  - Dừng: docker-compose down"
echo "  - Xem logs: docker-compose logs -f"
echo "  - Vào container: docker-compose exec app bash"
