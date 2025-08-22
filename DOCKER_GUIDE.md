# Docker Setup Guide

## Overview

This guide provides instructions for running the Cache Benchmark Tool using Docker, which simplifies the environment setup and ensures consistent results across different systems.

## Prerequisites

- **Docker**: Version 20.10+ with Docker Compose
- **Docker Compose**: Version 2.0+
- **Git**: For cloning the repository

## Quick Start

### 1. Clone and Setup

```bash
# Clone the repository
git clone <repository-url>
cd cache-benchmark

# Copy environment template
cp .env.example .env
```

### 2. Run with Docker

```bash
# Start all services and run benchmark
docker-compose --profile benchmark up --build

# Or run in detached mode
docker-compose --profile benchmark up --build -d
```

## Docker Services

### Available Services

1. **Redis** (`redis`): Redis 8.2 server
2. **Memcached** (`memcached`): Memcached 1.6 server  
3. **Application** (`app`): PHP 8.2 with benchmarking tool
4. **Nginx** (`nginx`): Web interface for viewing results (optional)

### Service Profiles

- **`benchmark`**: Production benchmark environment
- **`dev`**: Development environment with hot reload
- **`web`**: Web interface for viewing results

## Usage Scenarios

### 1. Quick Benchmark Run

```bash
# Run a complete benchmark suite
docker-compose --profile benchmark up --build

# View results
ls -la results/
```

### 2. Development Environment

```bash
# Start development environment
docker-compose --profile dev up --build

# Run benchmark with custom parameters
docker-compose exec app-dev php bin/benchmark.php --iterations=100 --concurrent=5

# View logs
docker-compose logs app-dev
```

### 3. Web Interface

```bash
# Start with web interface
docker-compose --profile benchmark --profile web up --build

# Access web interface
open http://localhost:8080
```

### 4. Custom Configuration

```bash
# Edit environment variables
nano .env

# Run with custom config
docker-compose --profile benchmark up --build
```

## Docker Commands Reference

### Basic Operations

```bash
# Build and start services
docker-compose --profile benchmark up --build

# Start services in background
docker-compose --profile benchmark up -d

# Stop services
docker-compose down

# View logs
docker-compose logs app

# Execute commands in container
docker-compose exec app php bin/benchmark.php --help

# Clean up
docker-compose down -v --remove-orphans
```

### Development Commands

```bash
# Start development environment
docker-compose --profile dev up --build

# Run tests
docker-compose exec app-dev composer test

# Install dependencies
docker-compose exec app-dev composer install

# Run specific benchmark
docker-compose exec app-dev php bin/benchmark.php --iterations=50
```

### Web Interface Commands

```bash
# Start with web interface
docker-compose --profile benchmark --profile web up --build

# View results in browser
open http://localhost:8080

# Check nginx logs
docker-compose logs nginx
```

## Configuration

### Environment Variables

The following environment variables can be configured in `.env`:

```env
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
```

### Docker Compose Configuration

Key configuration options in `docker-compose.yml`:

- **Health Checks**: Services wait for dependencies to be healthy
- **Volume Mounts**: Results directory is mounted for persistence
- **Network**: Isolated network for service communication
- **Profiles**: Different service combinations for different use cases

## Troubleshooting

### Common Issues

#### 1. Port Conflicts

```bash
# Check if ports are in use
lsof -i :6379
lsof -i :11211
lsof -i :8080

# Use different ports
docker-compose -f docker-compose.yml -f docker-compose.override.yml up
```

#### 2. Permission Issues

```bash
# Fix file permissions
chmod +x bin/benchmark.php
chmod +x docker/entrypoint.sh

# Create results directory
mkdir -p results
chmod 755 results
```

#### 3. Memory Issues

```bash
# Increase Docker memory limit
# In Docker Desktop: Settings > Resources > Memory

# Or run with custom memory settings
docker-compose run --memory=2g app php bin/benchmark.php
```

#### 4. Build Failures

```bash
# Clean build cache
docker-compose build --no-cache

# Remove old images
docker system prune -a

# Rebuild specific service
docker-compose build app
```

### Debugging

#### View Service Logs

```bash
# View all logs
docker-compose logs

# View specific service logs
docker-compose logs app
docker-compose logs redis
docker-compose logs memcached

# Follow logs in real-time
docker-compose logs -f app
```

#### Access Container Shell

```bash
# Access application container
docker-compose exec app bash

# Check PHP extensions
docker-compose exec app php -m

# Test connections
docker-compose exec app php -r "
\$redis = new Redis();
\$redis->connect('redis', 6379);
echo 'Redis: ' . \$redis->ping() . PHP_EOL;

\$memcached = new Memcached();
\$memcached->addServer('memcached', 11211);
echo 'Memcached: ' . (\$memcached->set('test', 'value') ? 'OK' : 'FAIL') . PHP_EOL;
"
```

#### Check Service Health

```bash
# Check Redis
docker-compose exec redis redis-cli ping

# Check Memcached
docker-compose exec memcached sh -c "echo 'version' | nc localhost 11211"

# Check application health
docker-compose exec app php -r "echo 'PHP: ' . phpversion() . PHP_EOL;"
```

## Performance Optimization

### Docker Settings

```bash
# Increase Docker resources
# Docker Desktop: Settings > Resources
# - Memory: 4GB+
# - CPUs: 2+
# - Disk: 20GB+

# Use Docker BuildKit
export DOCKER_BUILDKIT=1
```

### Container Optimization

```bash
# Run with optimized settings
docker-compose run --cpus=2 --memory=2g app php bin/benchmark.php

# Use host networking (Linux only)
docker-compose run --network=host app php bin/benchmark.php
```

## Production Deployment

### Production Configuration

```bash
# Create production compose file
cp docker-compose.yml docker-compose.prod.yml

# Edit for production settings
nano docker-compose.prod.yml
```

### Security Considerations

```bash
# Use secrets for sensitive data
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up

# Restrict network access
# Edit docker-compose.yml to remove port mappings
```

### Monitoring

```bash
# Monitor resource usage
docker stats

# Monitor logs
docker-compose logs -f

# Health checks
docker-compose ps
```

## Advanced Usage

### Custom Dockerfile

```dockerfile
# Create custom Dockerfile
FROM php:8.2-fpm

# Add custom extensions or configurations
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Copy custom configuration
COPY custom-php.ini /usr/local/etc/php/conf.d/
```

### Multi-Stage Builds

```dockerfile
# Development stage
FROM php:8.2-fpm AS dev
# Install development tools

# Production stage
FROM php:8.2-fpm AS prod
# Copy from dev stage
COPY --from=dev /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
```

### Custom Networks

```yaml
# Create custom network
networks:
  cache-benchmark-network:
    driver: bridge
    ipam:
      config:
        - subnet: 172.20.0.0/16
```

## Integration with CI/CD

### GitHub Actions Example

```yaml
name: Cache Benchmark
on: [push, pull_request]

jobs:
  benchmark:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run benchmark
        run: |
          docker-compose --profile benchmark up --build --exit-code-from app
      - name: Upload results
        uses: actions/upload-artifact@v3
        with:
          name: benchmark-results
          path: results/
```

### GitLab CI Example

```yaml
cache_benchmark:
  stage: test
  services:
    - docker:dind
  script:
    - docker-compose --profile benchmark up --build --exit-code-from app
  artifacts:
    paths:
      - results/
    expire_in: 1 week
```

## Best Practices

### 1. Resource Management

- Set appropriate memory and CPU limits
- Use health checks for service dependencies
- Monitor resource usage during benchmarks

### 2. Data Persistence

- Mount results directory as volume
- Use named volumes for cache data
- Backup important results

### 3. Security

- Don't expose unnecessary ports
- Use secrets for sensitive configuration
- Keep base images updated

### 4. Performance

- Use multi-stage builds for smaller images
- Optimize Dockerfile layers
- Use appropriate base images

## Support

### Getting Help

1. Check the logs: `docker-compose logs`
2. Verify configuration: `docker-compose config`
3. Test connectivity: Use the debug commands above
4. Check Docker resources: `docker system df`

### Useful Commands

```bash
# System information
docker version
docker-compose version
docker system info

# Clean up
docker system prune
docker volume prune
docker network prune

# Performance
docker stats
docker system df
```

## Conclusion

Docker provides a consistent, reproducible environment for running cache benchmarks. The setup includes:

- **Isolated services** for Redis and Memcached
- **Health checks** for reliable startup
- **Volume mounts** for result persistence
- **Web interface** for result visualization
- **Development environment** for testing

This setup eliminates environment-specific issues and makes the benchmarking tool portable across different systems.
