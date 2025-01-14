FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    dos2unix \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /app

COPY . .

RUN chmod +x composer.phar

RUN php composer.phar install --no-scripts --no-interaction

RUN find . -type f -exec dos2unix {} \;

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
