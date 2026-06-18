FROM node:20-alpine AS build

WORKDIR /var/www

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM php:8.4-fpm

# nginx + supervisor run the web server; the rest are PHP extension build deps.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        curl \
        unzip \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        libonig-dev \
        libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        zip \
        exif \
        pcntl \
        gd \
        intl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY --from=build /var/www/public/build public/build

COPY . .

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN composer install --no-dev --optimize-autoloader --no-interaction

# PHP runtime tuning (image uploads + memory headroom)
RUN { \
      echo 'upload_max_filesize=64M'; \
      echo 'post_max_size=64M'; \
      echo 'memory_limit=256M'; \
    } > /usr/local/etc/php/conf.d/zz-app.ini

# nginx: serve /public on :8080, hand .php to php-fpm on 127.0.0.1:9000
RUN printf '%s\n' \
    'server {' \
    '    listen 8080;' \
    '    server_name _;' \
    '    root /var/www/public;' \
    '    index index.php;' \
    '    client_max_body_size 64M;' \
    '    location / { try_files $uri $uri/ /index.php?$query_string; }' \
    '    location ~ \.php$ {' \
    '        fastcgi_pass 127.0.0.1:9000;' \
    '        fastcgi_index index.php;' \
    '        include fastcgi_params;' \
    '        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;' \
    '        fastcgi_param DOCUMENT_ROOT $realpath_root;' \
    '    }' \
    '    location ~ /\.(?!well-known).* { deny all; }' \
    '}' > /etc/nginx/sites-available/default

# supervisor: keep php-fpm + nginx running together, logs to container stdout
RUN printf '%s\n' \
    '[supervisord]' \
    'nodaemon=true' \
    'user=root' \
    '' \
    '[program:php-fpm]' \
    'command=php-fpm -F' \
    'autorestart=true' \
    'stdout_logfile=/dev/stdout' \
    'stdout_logfile_maxbytes=0' \
    'stderr_logfile=/dev/stderr' \
    'stderr_logfile_maxbytes=0' \
    '' \
    '[program:nginx]' \
    'command=nginx -g "daemon off;"' \
    'autorestart=true' \
    'stdout_logfile=/dev/stdout' \
    'stdout_logfile_maxbytes=0' \
    'stderr_logfile=/dev/stderr' \
    'stderr_logfile_maxbytes=0' \
    > /etc/supervisor/conf.d/app.conf

EXPOSE 8080

# Web container: prepare the app, then run nginx + php-fpm under supervisor.
# Worker containers override this command (see docker-compose.yml) to run queue:work.
CMD ["sh", "-c", "if [ \"$MIGRATE_ON_START\" = \"true\" ]; then php artisan migrate --force; fi; php artisan storage:link || true; php artisan config:cache && php artisan route:cache && php artisan view:cache; chown -R www-data:www-data storage bootstrap/cache; exec /usr/bin/supervisord -c /etc/supervisor/conf.d/app.conf"]
