
FROM php:8.2-apache

# Install required PHP extensions
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql curl \
    && rm -rf /var/lib/apt/lists/*

# Ensure only prefork MPM is enabled
RUN a2dismod mpm_event mpm_worker mpm_prefork || true
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load \
          /etc/apache2/mods-enabled/mpm_*.conf \
    && a2enmod mpm_prefork rewrite

# Verify Apache configuration during build
RUN apache2ctl -M | grep mpm

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

# Start Apache using Railway's assigned port
CMD ["bash", "-c", "echo '=== RUNTIME MPM SYMLINKS ==='; ls -la /etc/apache2/mods-enabled/mpm*; echo '=== RUNTIME MPM DIRECTIVES ==='; grep -RniE '^[[:space:]]*LoadModule[[:space:]]+mpm_' /etc/apache2 || true; echo '=== STARTING APACHE ==='; PORT=${PORT:-8080}; sed -i \"s/Listen 80/Listen ${PORT}/\" /etc/apache2/ports.conf; sed -i \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT}>/\" /etc/apache2/sites-available/000-default.conf; apache2-foreground"]
