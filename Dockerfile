
FROM php:8.2-apache

# Install required PHP extensions
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql curl \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set application directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Configure Apache to use Railway's PORT
CMD ["bash", "-c", "PORT=${PORT:-80}; sed -i \"s/Listen 80/Listen ${PORT}/\" /etc/apache2/ports.conf; sed -i \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT}>/\" /etc/apache2/sites-available/000-default.conf; apache2-foreground"]
