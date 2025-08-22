#!/bin/bash
set -e

# Function to wait for services to be ready
wait_for_service() {
    local host=$1
    local port=$2
    local service_name=$3
    
    echo "Waiting for $service_name to be ready..."
    while ! nc -z $host $port; do
        sleep 1
    done
    echo "$service_name is ready!"
}

# Wait for Redis and Memcached to be ready
wait_for_service redis 6379 "Redis"
wait_for_service memcached 11211 "Memcached"

# Check if .env file exists, create from example if not
if [ ! -f .env ]; then
    echo "Creating .env file from template..."
    if [ -f .env.example ]; then
        cp .env.example .env
    else
        echo "No .env.example found, creating basic .env file..."
        cat > .env << EOF
# Redis Configuration
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0
REDIS_TIMEOUT=5.0

# Memcached Configuration
MEMCACHED_HOST=memcached
MEMCACHED_PORT=11211
MEMCACHED_WEIGHT=100

# Benchmark Configuration
BENCHMARK_ITERATIONS=1000
BENCHMARK_CONCURRENT_CONNECTIONS=10
BENCHMARK_WARMUP_ITERATIONS=100
BENCHMARK_OUTPUT_DIR=/app/results
BENCHMARK_LOG_LEVEL=INFO

# Data Patterns
SMALL_VALUE_SIZE=1024
MEDIUM_VALUE_SIZE=10240
LARGE_VALUE_SIZE=102400
EOF
    fi
fi

# Create results directory if it doesn't exist
mkdir -p results

# Set proper permissions
chmod 755 results
chmod +x bin/benchmark.php

# Install dependencies if vendor directory doesn't exist
if [ ! -d vendor ]; then
    echo "Installing PHP dependencies..."
    composer install --no-dev --optimize-autoloader
fi

# Run health checks
echo "Running health checks..."
php -m | grep -E "(redis|memcached)" || {
    echo "ERROR: Required PHP extensions not loaded!"
    echo "Available extensions:"
    php -m
    exit 1
}

# Test connections
echo "Testing cache connections..."
php -r "
try {
    \$redis = new Redis();
    \$redis->connect('redis', 6379);
    echo '✓ Redis connection successful\n';
} catch (Exception \$e) {
    echo '✗ Redis connection failed: ' . \$e->getMessage() . '\n';
    exit(1);
}

try {
    \$memcached = new Memcached();
    \$memcached->addServer('memcached', 11211);
    \$memcached->set('test', 'value');
    echo '✓ Memcached connection successful\n';
} catch (Exception \$e) {
    echo '✗ Memcached connection failed: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

echo "Environment setup complete!"

# Execute the main command
exec "$@"
