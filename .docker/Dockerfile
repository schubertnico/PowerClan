# PowerClan - PHP 8.4 mit Apache
FROM php:8.4-apache

# System-Abhängigkeiten installieren
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# PHP-Erweiterungen installieren
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    mysqli \
    pdo \
    pdo_mysql

# Apache mod_rewrite aktivieren
RUN a2enmod rewrite

# Apache-Konfiguration für .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# PHP-Konfiguration kopieren
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Arbeitsverzeichnis setzen
WORKDIR /var/www/html

# Apache auf Port 80 konfigurieren
EXPOSE 80

# Apache im Vordergrund starten
CMD ["apache2-foreground"]
