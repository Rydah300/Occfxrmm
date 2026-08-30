FROM php:8.2-cli-bullseye

# Install PostgreSQL driver
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql

# Copy all files
COPY . /app

WORKDIR /app

# Start PHP server
CMD php -S 0.0.0.0:8080 -t /app
