# LULU-OPEN — image de production (PHP 8.2 + Apache)
FROM php:8.2-apache

# Dépendances système + poppler-utils (pdftotext, pour la lecture des CV PDF)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev libpng-dev libjpeg-dev libwebp-dev libonig-dev libicu-dev \
        poppler-utils unzip fonts-dejavu-core \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql mbstring zip intl gd \
    && a2enmod rewrite headers deflate expires \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Autoriser le .htaccess (front controller) sur le DocumentRoot
RUN printf '<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
      > /etc/apache2/conf-available/lulu-dir.conf \
    && a2enconf lulu-dir

# Plafonner les workers Apache : chaque requête PHP ouvre 1 connexion MySQL.
# Le plan MySQL gratuit (Clever Cloud DEV) est limité à 5 connexions → on cape à 4
# (les requêtes en surplus sont mises en file, pas d'erreur "too many connections").
RUN printf '<IfModule mpm_prefork_module>\n    StartServers 2\n    MinSpareServers 2\n    MaxSpareServers 3\n    MaxRequestWorkers 4\n    MaxConnectionsPerChild 500\n</IfModule>\n' \
      > /etc/apache2/mods-available/mpm_prefork.conf

# Réglages PHP production
RUN { \
      echo 'upload_max_filesize=10M'; \
      echo 'post_max_size=12M'; \
      echo 'memory_limit=256M'; \
      echo 'max_execution_time=60'; \
      echo 'expose_php=Off'; \
    } > /usr/local/etc/php/conf.d/lulu.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html

# Dépendances PHP (production)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress || true

# Dossiers inscriptibles (uploads + logs) + entrypoint exécutable
RUN mkdir -p uploads/cv uploads/photos uploads/verifications logs/mail_previews \
    && chown -R www-data:www-data uploads logs \
    && chmod +x /var/www/html/docker/entrypoint.sh

# Lancé via `sh` : indépendant du bit exécutable stocké par git (Windows).
ENTRYPOINT ["sh", "/var/www/html/docker/entrypoint.sh"]
