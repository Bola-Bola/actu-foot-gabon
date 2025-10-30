# Utilise une image PHP avec Apache
FROM php:8.2-apache

# Installe les extensions nécessaires à Laravel
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Active mod_rewrite pour Apache (important pour Laravel)
RUN a2enmod rewrite

# Copie les fichiers du projet dans le conteneur
COPY . /var/www/html

# Définit le dossier de travail
WORKDIR /var/www/html

# Installe Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Installe les dépendances Laravel
RUN composer install --no-dev --optimize-autoloader

# Donne les bonnes permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose le port 80
EXPOSE 80

# Commande de démarrage
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
