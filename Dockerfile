t FROM php:8.2-fpm

# Set working directory
WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libmemcached-dev \
    zlib1g-dev \
    libssl-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Memcached extension with proper configuration
RUN pecl install memcached && docker-php-ext-enable memcached

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install netcat for health checks
RUN apt-get update && apt-get install -y netcat-traditional && rm -rf /var/lib/apt/lists/*

# Copy composer files
COPY composer.json composer.lock* ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy application code
COPY . .

# Create results directory
RUN mkdir -p results && chmod 755 results

# Make scripts executable
RUN chmod +x bin/benchmark.php
RUN chmod +x docker/entrypoint.sh

# Ensure entrypoint script is executable
RUN ls -la docker/entrypoint.sh

# Set environment variables
ENV PHP_MEMORY_LIMIT=512M
ENV PHP_MAX_EXECUTION_TIME=300

# Expose port (if needed for web interface)
EXPOSE 9000

# Use entrypoint script
ENTRYPOINT ["/bin/bash", "docker/entrypoint.sh"]

# Default command
CMD ["php", "bin/benchmark.php"]
