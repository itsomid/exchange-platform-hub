FROM nginx:stable-alpine

# Argument for selecting the Nginx config file
ARG NGINX_CONF

# Ensure necessary directories exist
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copy the correct Nginx configuration based on the build argument
COPY ./nginx/${NGINX_CONF} /etc/nginx/conf.d/default.conf

# Expose port 80
EXPOSE 80

# Start Nginx
CMD ["nginx", "-g", "daemon off;"]