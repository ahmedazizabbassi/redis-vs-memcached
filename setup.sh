#!/bin/bash

# Cache Benchmark Tool Setup Script
# This script helps set up the benchmarking environment

set -e

echo "=== Cache Benchmark Tool Setup ==="
echo "Setting up the benchmarking environment..."
echo

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed. Please install PHP 8.0 or higher."
    exit 1
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "✓ PHP version: $PHP_VERSION"

# Check required PHP extensions
REQUIRED_EXTENSIONS=("redis" "memcached" "json" "mbstring")
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if ! php -m | grep -q "^$ext$"; then
        MISSING_EXTENSIONS+=("$ext")
    else
        echo "✓ PHP extension: $ext"
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -ne 0 ]; then
    echo "❌ Missing required PHP extensions: ${MISSING_EXTENSIONS[*]}"
    echo "Please install the missing extensions:"
    for ext in "${MISSING_EXTENSIONS[@]}"; do
        case $ext in
            "redis")
                echo "  - For Redis: sudo pecl install redis"
                ;;
            "memcached")
                echo "  - For Memcached: sudo pecl install memcached"
                ;;
            "json"|"mbstring")
                echo "  - For $ext: Usually included with PHP, check your PHP installation"
                ;;
        esac
    done
    exit 1
fi

# Check if Composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Composer is not installed. Please install Composer first."
    echo "Visit: https://getcomposer.org/download/"
    exit 1
fi

echo "✓ Composer is installed"

# Install dependencies
echo
echo "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo
    echo "Creating .env file from template..."
    cp env.example .env
    echo "✓ Created .env file"
    echo "⚠️  Please edit .env file with your Redis and Memcached connection details"
else
    echo "✓ .env file already exists"
fi

# Create results directory
if [ ! -d results ]; then
    echo "Creating results directory..."
    mkdir -p results
    echo "✓ Created results directory"
else
    echo "✓ Results directory already exists"
fi

# Make benchmark script executable
chmod +x bin/benchmark.php
echo "✓ Made benchmark script executable"

# Check if Redis is running
echo
echo "Checking Redis server..."
if command -v redis-cli &> /dev/null; then
    if redis-cli ping &> /dev/null; then
        echo "✓ Redis server is running"
    else
        echo "⚠️  Redis server is not responding. Please start Redis:"
        echo "   sudo systemctl start redis"
        echo "   or"
        echo "   redis-server"
    fi
else
    echo "⚠️  redis-cli not found. Please ensure Redis is installed and running."
fi

# Check if Memcached is running
echo
echo "Checking Memcached server..."
if command -v memcached &> /dev/null; then
    if echo "version" | nc localhost 11211 &> /dev/null; then
        echo "✓ Memcached server is running"
    else
        echo "⚠️  Memcached server is not responding. Please start Memcached:"
        echo "   sudo systemctl start memcached"
        echo "   or"
        echo "   memcached -d -p 11211"
    fi
else
    echo "⚠️  memcached not found. Please ensure Memcached is installed and running."
fi

echo
echo "=== Setup Complete ==="
echo
echo "Next steps:"
echo "1. Edit .env file with your Redis and Memcached connection details"
echo "2. Ensure Redis and Memcached servers are running"
echo "3. Run the benchmark: php bin/benchmark.php"
echo "4. View results in the results/ directory"
echo
echo "For help: php bin/benchmark.php --help"
echo
echo "Happy benchmarking! 🚀"
