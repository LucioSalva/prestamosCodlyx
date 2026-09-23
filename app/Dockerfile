FROM php:8.3-apache

# --- Extensiones PHP necesarias para PostgreSQL ---
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/*

# --- Apache: document root en /public + enrutado al front controller ---
RUN a2enmod rewrite headers
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# --- Ajustes de PHP para producción ---
RUN { \
      echo 'display_errors=Off'; \
      echo 'log_errors=On'; \
      echo 'error_log=/proc/self/fd/2'; \
      echo 'expose_php=Off'; \
      echo 'date.timezone=America/Mexico_City'; \
    } > /usr/local/etc/php/conf.d/zz-app.ini

# --- Código de la aplicación ---
WORKDIR /var/www/html
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
