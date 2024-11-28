FROM nginx:stable-alpine

# environment arguments
ARG UID
ARG GID
ARG USER
ARG NGINX_CONF

ENV UID=${UID}
ENV GID=${GID}
ENV USER=${USER}

# Creating user and group
RUN addgroup -g ${GID} ${USER}
RUN adduser -G ${USER} -D -s /bin/sh -u ${UID} ${USER}

# Modify nginx configuration to use the new user's privileges
RUN sed -i "s/user nginx/user '${USER}'/g" /etc/nginx/nginx.conf

# Make html directory
RUN mkdir -p /var/www/html

# Copy the correct NGINX configuration based on the build argument
COPY ./nginx/${NGINX_CONF} /etc/nginx/conf.d/default.conf