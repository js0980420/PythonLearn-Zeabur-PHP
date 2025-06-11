# 🐳 PythonLearn-Zeabur-PHP Dockerfile
# 純 HTTP 輪詢架構 - 專為 Zeabur 單端口環境設計

FROM php:8.2-cli

# 安裝系統依賴
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    curl \
    && docker-php-ext-install zip pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 設置工作目錄
WORKDIR /app

# 複製 composer 文件
COPY composer.json composer.lock ./

# 安裝 PHP 依賴
RUN composer install --no-dev --optimize-autoloader

# 複製應用程式代碼
COPY . .

# 創建必要目錄並設置權限
RUN mkdir -p data storage \
    && chmod -R 755 public \
    && chmod -R 777 data \
    && chmod -R 777 storage

# 暴露端口（僅 HTTP）
EXPOSE 8080

# 健康檢查
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/health.php || exit 1

# 啟動命令 - 純 HTTP 服務器
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"] 