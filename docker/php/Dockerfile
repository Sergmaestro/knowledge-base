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
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath

COPY docker/php/disable-opcache.ini /usr/local/etc/php/conf.d/disable-opcache.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN chmod +x /usr/bin/composer

# Create application user
ARG USER_ID=1000
ARG GROUP_ID=1000
RUN groupadd -g ${GROUP_ID} dockeruser \
    && useradd -u ${USER_ID} -g dockeruser -m dockeruser

# Set up application directory
WORKDIR /var/www
RUN chown -R dockeruser:dockeruser /var/www

# Copy application code
COPY . /var/www

# Install dependencies
RUN composer install --optimize-autoloader

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
