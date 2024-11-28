FROM composer:latest

# environment arguments
ARG UID
ARG GID
ARG USER

ENV UID=${UID}
ENV GID=${GID}
ENV USER=${USER}


# Creating user and group
# Create a user group with a unique name
RUN addgroup -g ${GID} ${USER} || true \
    && adduser -G ${USER} -D -s /bin/sh -u ${UID} ${USER} || true


WORKDIR /var/www/html
#
#COPY ./src .
#RUN chown -R ${USER}:${USER} /var/www/html