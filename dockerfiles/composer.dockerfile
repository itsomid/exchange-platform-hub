FROM composer:latest

# environment arguments
ARG UID
ARG GID
ARG USER

ENV UID=${UID}
ENV GID=${GID}
ENV USER=omid


# Creating user and group
RUN addgroup -g ${GID}  ${USER}
RUN adduser -G ${USER} -D -s /bin/sh -u ${UID} ${USER}


WORKDIR /var/www/html
#
#COPY ./src .
#RUN chown -R ${USER}:${USER} /var/www/html