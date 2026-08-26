FROM php:8.2-cli

# Bibliothèques système nécessaires pour compiler certaines extensions PHP (ex: gd, curl)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libcurl4-openssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mysqli mbstring gd fileinfo curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . /app

# Railway fournit automatiquement la variable $PORT
CMD php -S 0.0.0.0:$PORT -t /app
