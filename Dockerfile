FROM php:8.2-cli

# Install PostgreSQL driver
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql

# Install PHP built-in server
RUN apt-get install -y php8.2-cli php8.2-common

# Set working directory
WORKDIR /app

# Copy all files
COPY . .

# Expose port
EXPOSE 8080

# Start PHP server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "."]
