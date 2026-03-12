FROM php:8.2-apache
EXPOSE 80

RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    zlib1g-dev \
    libzip-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip mbstring curl \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite

# Allow .htaccess overrides and rewrite rules in the web root
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/system/uploads \
    && chmod -R 775 /var/www/html/system/cache \
    && chmod -R 775 /var/www/html/ui/cache \
    && chmod -R 775 /var/www/html/ui/compiled

WORKDIR /var/www/html