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
    supervisor \
    default-mysql-client \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sockets \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 設置工作目錄
WORKDIR /app

# 複製依賴文件
COPY composer.json composer.lock ./

# 安裝 PHP 依賴
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 複製應用代碼
COPY . .

# 創建必要目錄
RUN mkdir -p /app/data /app/logs /app/storage /app/sessions /app/temp \
    && chmod -R 755 /app/data /app/logs /app/storage /app/sessions /app/temp

# 複製 Supervisor 配置
COPY supervisor.conf /etc/supervisor/conf.d/pythonlearn.conf

# 創建啟動腳本
RUN echo '#!/bin/bash\n\
echo "🚀 Starting PythonLearn Collaboration Platform..."\n\
echo "📊 Environment: ${ENVIRONMENT:-production}"\n\
echo "🗄️ Database: ${MYSQL_HOST:-mysql}:${MYSQL_PORT:-3306}"\n\
echo "🌐 Domain: ${ZEABUR_DOMAIN:-localhost}"\n\
\n\
# 啟動 Supervisor 來管理多個服務\n\
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf -n\n\
' > /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

# 健康檢查
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/api/health || exit 1

# 暴露端口
EXPOSE 8080 8081

# 啟動腳本
CMD ["/usr/local/bin/start.sh"] 