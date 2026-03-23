FROM php:8.4-apache

# Extensiones del sistema necesarias para ldap y zip
RUN apt-get update && apt-get install -y \
    libldap2-dev \
    libzip-dev \
    libonig-dev \
    msmtp \
    msmtp-mta \
    --no-install-recommends && rm -rf /var/lib/apt/lists/*

# Extensiones PHP requeridas por el sistema
RUN docker-php-ext-install \
    mysqli \
    ldap \
    zip \
    mbstring \
    fileinfo

# Habilitar mod_rewrite para el .htaccess
RUN a2enmod rewrite

# Copiar configuración personalizada de PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/sgpfl.ini

# Copiar configuración de msmtp (relay SMTP, equivalente al sendmail.ini de XAMPP)
COPY docker/msmtp/msmtprc /etc/msmtprc
RUN chmod 600 /etc/msmtprc && chown www-data:www-data /etc/msmtprc

# Configurar Apache: AllowOverride para que funcione el .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
