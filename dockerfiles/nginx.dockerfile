FROM nginx:stable-alpine

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

# Modify nginx configuration to use the new user's priviledges for starting it.
RUN sed -i "s/user nginx/user '${USER}'/g" /etc/nginx/nginx.conf

# Copies nginx configurations to override the default.
ADD ./nginx/*.conf /etc/nginx/conf.d/

# Make html directory
RUN mkdir -p /var/www/html

#COPY ./src/ /var/www/html
#RUN chown -R ${USER}:${USER} /var/www/html