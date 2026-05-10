FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git curl zip unzip libicu-dev libpq-dev libonig-dev \
    && docker-php-ext-install intl pdo pdo_mysql opcache mbstring

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf
RUN a2enmod rewrite

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data var/

EXPOSE 80