ARG PHP_VERSION

FROM php:${PHP_VERSION}-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    && docker-php-ext-install pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /srv/web

COPY . .

RUN chown -R www-data:www-data /srv/web

EXPOSE 9000

CMD ["php-fpm"]
