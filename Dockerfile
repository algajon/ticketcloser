# Use ServerSideUp's optimized PHP 8.2 + Nginx container (Perfect for Laravel)
FROM serversideup/php:8.2-fpm-nginx

# Switch to root to install Node.js (needed for npm run build) and PostgreSQL driver
USER root
RUN apt-get update && \
    apt-get install -y default-mysql-client default-libmysqlclient-dev && \
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Fix permission for web root
RUN chown -R www-data:www-data /var/www/html

# Switch back to unprivileged user for security
USER www-data

# Set working directory
WORKDIR /var/www/html

# Copy the application code
COPY --chown=www-data:www-data . .

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-interaction --no-progress --no-dev

# Install Node dependencies and compile frontend
RUN npm install
RUN npm run build

# Clear and cache configurations
RUN php artisan optimize:clear \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache
