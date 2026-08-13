FROM php:8.5.8-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl default-mysql-client libicu-dev libonig-dev libzip-dev unzip \
    && docker-php-ext-install -j"$(nproc)" bcmath intl mbstring pdo_mysql zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY infra/docker/api/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY infra/docker/api/entrypoint.sh /usr/local/bin/portfolio-api-entrypoint

RUN chmod +x /usr/local/bin/portfolio-api-entrypoint

WORKDIR /var/www/html
EXPOSE 80
ENTRYPOINT ["portfolio-api-entrypoint"]
CMD ["apache2-foreground"]
