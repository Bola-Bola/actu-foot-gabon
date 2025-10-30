# Utilise une image PHP avec Apache
FROM php:8.2-apache

# Installe les extensions nécessaires à Laravel + PostgreSQL + SQLite
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpq-dev \
    libsqlite3-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_sqlite zip \
    && docker-php-ext-enable pdo_pgsql pdo_sqlite

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
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Crée un fichier SQLite si nécessaire
RUN touch /var/www/html/database/database.sqlite \
    && chown www-data:www-data /var/www/html/database/database.sqlite \
    && chmod 664 /var/www/html/database/database.sqlite

# Expose le port 80
EXPOSE 80

# Commande de démarrage
CMD ["apache2-foreground"]
