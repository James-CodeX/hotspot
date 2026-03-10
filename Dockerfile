# Use the official PHP image with Apache
FROM php:8.2-apache
EXPOSE 80

# Install necessary PHP extensions
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    zlib1g-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && docker-php-ext-install zip \
    && a2dismod mpm_event mpm_worker || true \
    && a2enmod mpm_prefork rewrite \
    && printf "ServerName localhost\n" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# copy contents into directory
COPY . /var/www/html
COPY docker/start-apache.sh /usr/local/bin/start-apache.sh

# Set appropriate permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html
RUN chmod +x /usr/local/bin/start-apache.sh

# Set working directory
WORKDIR /var/www/html

CMD ["/usr/local/bin/start-apache.sh"]