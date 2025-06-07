FROM php:8.2-cli

# 安裝系統依賴
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    default-mysql-client \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sockets \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 設置工作目錄
WORKDIR /app

# 複製 composer 檔案 (確保 composer.lock 也被複製)
COPY composer.json composer.lock* ./

# 安裝 PHP 依賴 (添加 --no-scripts 參數)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# 複製應用代碼
COPY . .

# 創建必要目錄
RUN mkdir -p /app/data /app/logs /app/storage /app/sessions /app/temp \
    && chmod -R 755 /app/data /app/logs /app/storage /app/sessions /app/temp

# 創建啟動腳本
RUN echo '#!/bin/bash\n\
echo "🚀 Starting PythonLearn Collaboration Platform..."\n\
echo "📊 Environment: production"\n\
echo "🗄️ Database: ${MYSQL_HOST:-mysql}:${MYSQL_PORT:-3306}"\n\
echo "🌐 Domain: ${ZEABUR_WEB_DOMAIN:-localhost}"\n\
\n\
# 啟動 WebSocket 服務器（背景執行）\n\
echo "🔌 Starting WebSocket server on port 8081..."\n\
php websocket/server.php > /app/logs/websocket.log 2>&1 &\n\
\n\
# 等待 WebSocket 服務器啟動\n\
sleep 3\n\
\n\
# 啟動 PHP Web 服務器\n\
echo "🌐 Starting Web server on port 8080..."\n\
exec php -S 0.0.0.0:8080 -t public router.php\n\
' > /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

# 健康檢查
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/health.php || exit 1

# 暴露端口
EXPOSE 8080 8081

# 啟動腳本
CMD ["/usr/local/bin/start.sh"] 