FROM ubuntu:22.04

# Install PHP + PostgreSQL driver from Ubuntu repos
RUN apt-get update && apt-get install -y \
    software-properties-common \
    && add-apt-repository ppa:ondrej/php \
    && apt-get update && apt-get install -y \
    php8.2 \
    php8.2-cli \
    php8.2-fpm \
    php8.2-pgsql \
    php8.2-curl \
    php8.2-mbstring \
    php8.2-xml \
    && apt-get clean

# Set working directory
WORKDIR /app

# Copy all files
COPY . .

# Expose port
EXPOSE 8080

# Start PHP server
CMD php -S 0.0.0.0:8080 -t .
