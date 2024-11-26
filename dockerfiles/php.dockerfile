FROM php:8.4.1-fpm-alpine

# environment arguments
ARG UID
ARG GID
ARG USER

ENV UID=${UID}
ENV GID=${GID}
ENV USER=omid

# Creating user and group
RUN addgroup -g ${GID}  ${USER}
RUN adduser -G ${USER}  -D -s /bin/sh -u ${UID} ${USER}

# Modify php fpm configuration to use the new user's priviledges.
RUN sed -i "s/user = www-data/user = '${USER}'/g" /usr/local/etc/php-fpm.d/www.conf
RUN sed -i "s/group = www-data/group = '${USER}'/g" /usr/local/etc/php-fpm.d/www.conf
RUN echo "php_admin_flag[log_errors] = on" >> /usr/local/etc/php-fpm.d/www.conf

# Installing php extensions
RUN apk update && apk upgrade
RUN docker-php-ext-install pdo pdo_mysql bcmath

# Installing php extensions

# Install extensions
RUN apk update && apk upgrade
RUN apk add --no-cache freetype libjpeg-turbo libpng libwebp libxpm \
    freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev libxpm-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-xpm \
    && docker-php-ext-install gd pdo pdo_mysql bcmath \
    && apk del freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev libxpm-dev

# Set permissions for Laravel storage and cache directories
RUN mkdir -p /var/www/html/storage/logs /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache && \
    chown -R ${USER}:${USER} /var/www/html/storage /var/www/html/bootstrap/cache

CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]