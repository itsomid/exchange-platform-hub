FROM php:8.4.1-fpm-alpine

# environment arguments
ARG UID
ARG GID
ARG USER

ENV UID=${UID}
ENV GID=${GID}
ENV USER=${USER}

# Create a user group with a unique name
RUN addgroup -g ${GID} ${USER} || true \
    && adduser -G ${USER} -D -s /bin/sh -u ${UID} ${USER} || true

# Modify php fpm configuration to use the new user's priviledges.
RUN sed -i "s/user = www-data/user = ${USER}/g" /usr/local/etc/php-fpm.d/www.conf
RUN sed -i "s/group = www-data/group = ${USER}/g" /usr/local/etc/php-fpm.d/www.conf
RUN echo "php_admin_flag[log_errors] = on" >> /usr/local/etc/php-fpm.d/www.conf

# Installing php extensions
RUN apk update && apk upgrade
RUN docker-php-ext-install pdo pdo_mysql bcmath

# Installing php extensions
RUN apk update && apk upgrade \
    && apk add --no-cache \
        mysql-client \
        libzip-dev \
        freetype libjpeg-turbo libpng libwebp libxpm \
        freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev libxpm-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-xpm \
    && docker-php-ext-install gd zip pdo pdo_mysql bcmath pcntl \
    && apk del freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev libxpm-dev



# Install Redis extension
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Set permissions for Laravel storage and cache directories
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache && \
    chown -R ${USER}:${USER} /var/www/html/storage /var/www/html/bootstrap/cache

COPY php.ini-production $PHP_INI_DIR/php.ini

CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]