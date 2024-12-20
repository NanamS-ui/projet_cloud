FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    dos2unix \
    && docker-php-ext-install pdo pdo_pgsql

# Définir le répertoire de travail
WORKDIR /app

# Copier les fichiers du projet dans le conteneur
COPY . .

# Rendre exécutable composer.phar si nécessaire
RUN chmod +x composer.phar

# Installer les dépendances PHP avec composer.phar
RUN php composer.phar install --no-scripts --no-interaction

# Corriger les problèmes de fin de ligne si nécessaire
RUN find . -type f -exec dos2unix {} \;

# Lancer les commandes de migration automatiquement
RUN php bin/console doctrine:database:create --if-not-exists || true
RUN php bin/console make:migration
RUN php bin/console doctrine:migrations:migrate --no-interaction || true

# Commande par défaut (optionnelle)
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
