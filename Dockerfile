# ⚠️ BUG #4 : cette image embarque Apache, mais la config nginx attend un process
#             PHP-FPM sur le port 9000. Remplacez par l'image php:8.2-fpm.
FROM php:8.2-fpm

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

COPY app/ .

RUN chown -R www-data:www-data /var/www/html
