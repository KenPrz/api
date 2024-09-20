# Use PHP 8.3 Apache as the base image
FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    # nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

# Enable Apache modules
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents
COPY . /var/www

# Remove the original avatars folder after copying
RUN rm -rf /var/www/public/avatars

# Set permissions
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www/storage

# Install application dependencies
RUN composer install --no-interaction --no-plugins --no-scripts

# # Install Node.js dependencies
# RUN npm install
# RUN npm run build

# Change DocumentRoot to public directory
RUN sed -i 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/000-default.conf

# Set PHP configurations for file upload
RUN echo "upload_max_filesize = 5M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 5M" >> /usr/local/etc/php/conf.d/uploads.ini

# Set up entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port 80
EXPOSE 80

# Use the entrypoint script
ENTRYPOINT ["docker-entrypoint.sh"]

# Start apache server
CMD ["apache2-foreground"]
