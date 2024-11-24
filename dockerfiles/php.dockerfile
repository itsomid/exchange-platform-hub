#FROM composer:latest as build

FROM php:8.3-fpm-alpine

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

#WORKDIR /var/www/html

#COPY ./src .
#COPY --from=build /usr/bin/composer /usr/bin/composer
#RUN composer install --ignore-platform-reqs --optimize-autoloader --no-interaction --no-progress --prefer-dist
# Installing redis extension
#RUN mkdir -p /usr/src/php/ext/redis \
#    && curl -fsSL https://github.com/phpredis/phpredis/archive/5.3.4.tar.gz | tar xvz -C /usr/src/php/ext/redis --strip 1 \
#    && echo 'redis' >> /usr/src/php-available-exts \
#    && docker-php-ext-install redis \

# Copy application code to the container
#RUN apk add --update nodejs npm
#RUN npm install
#RUN npm run build

#WORKDIR /var/www/html
#COPY ./src .
#RUN chown -R ${USER}:${USER} /var/www/html/storage \ && chmod -R 775 /var/www/html/storage
# Change ownership of the application code
#RUN chown -R ${USER}:${USER} /var/www/html


CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]