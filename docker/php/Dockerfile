FROM php:8.5-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    default-libmysqlclient-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN chmod +x /usr/bin/composer

RUN pecl install xdebug docker-php-ext-enable xdebug \
    && mkdir -p /var/www/storage/logs/xdebug

COPY . /var/www

WORKDIR /var/www

RUN composer install --optimize-autoloader --no-interaction

COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

ARG USER_ID=1000
ARG GROUP_ID=1000
RUN groupadd -g ${GROUP_ID} dockeruser \
    && useradd -u ${USER_ID} -g dockeruser -m dockeruser \
    && chown -R dockeruser:dockeruser /var/www

USER dockeruser

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
