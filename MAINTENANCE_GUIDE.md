# Maintenance and Development Guide

## Quick Reference

### Project Structure
```
cache-benchmark/
├── src/                    # Source code
├── bin/                    # Executable scripts
├── tests/                  # Unit tests
├── examples/               # Usage examples
├── results/                # Benchmark output (auto-created)
├── composer.json           # Dependencies
├── .env                    # Configuration
└── setup.sh               # Installation script
```

### Key Commands
```bash
# Install dependencies
composer install

# Run benchmarks
php bin/benchmark.php

# Run tests
composer test

# Setup environment
./setup.sh
```

## Common Tasks

### Adding a New Cache System

1. **Create Adapter Class**:
```php
// src/Cache/NewCacheAdapter.php
class NewCacheAdapter implements CacheAdapterInterface
{
    // Implement all interface methods
    // Follow existing adapter patterns
}
```

2. **Update Benchmark Engine**:
```php
// src/Benchmark/BenchmarkEngine.php
private function setupConnections(): void
{
    // Add new cache system initialization
    $this->newCache = new NewCacheAdapter($this->config->getNewCacheConfig());
}
```

3. **Add Configuration**:
```php
// src/Config/Configuration.php
public function getNewCacheConfig(): array
{
    return [
        'host' => $_ENV['NEWCACHE_HOST'] ?? '127.0.0.1',
        'port' => (int)($_ENV['NEWCACHE_PORT'] ?? 8080),
        // ... other settings
    ];
}
```

4. **Update Environment Template**:
```env
# .env
NEWCACHE_HOST=127.0.0.1
NEWCACHE_PORT=8080
```

### Adding New Benchmark Scenarios

1. **Create Benchmark Method**:
```php
// src/Benchmark/BenchmarkEngine.php
private function runNewScenarioBenchmark(): void
{
    $this->logger->info('Running new scenario benchmark');
    
    // Define test parameters
    $iterations = $this->config->getBenchmarkConfig()['iterations'];
    
    // Run tests for each cache system
    $this->results[] = $this->redis->benchmarkOperation(
        'NEW_SCENARIO',
        fn() => $this->redis->set('test_key', 'test_value'),
        $iterations
    );
    
    $this->results[] = $this->memcached->benchmarkOperation(
        'NEW_SCENARIO',
        fn() => $this->memcached->set('test_key', 'test_value'),
        $iterations
    );
}
```

2. **Add to Main Benchmark Suite**:
```php
public function runAllBenchmarks(): array
{
    // ... existing benchmarks ...
    $this->runNewScenarioBenchmark();
    // ... rest of benchmarks ...
}
```

### Troubleshooting

#### Common Issues

**1. PHP Extension Not Found**
```bash
# Check if extension is loaded
php -m | grep redis
php -m | grep memcached

# If not loaded, check php.ini
php --ini

# Add extension to php.ini
echo "extension=redis.so" >> /path/to/php.ini
echo "extension=memcached.so" >> /path/to/php.ini
```

**2. Connection Failures**
```bash
# Test Redis connection
redis-cli ping

# Test Memcached connection
echo "version" | nc localhost 11211

# Check server status
brew services list | grep -E "(redis|memcached)"
```

**3. Permission Issues**
```bash
# Fix file permissions
chmod +x bin/benchmark.php
chmod +x setup.sh

# Create results directory
mkdir -p results
chmod 755 results
```

**4. Memory Issues**
```bash
# Increase PHP memory limit
php -d memory_limit=512M bin/benchmark.php

# Or set in php.ini
memory_limit = 512M
```

#### Performance Issues

**1. Slow Benchmark Execution**
- Reduce iterations: `--iterations=100`
- Reduce concurrency: `--concurrent=5`
- Check system resources: `htop`, `iostat`

**2. Inconsistent Results**
- Run warmup iterations
- Ensure no other processes are using cache servers
- Check for network latency

**3. Memory Exhaustion**
- Monitor memory usage during tests
- Reduce batch sizes in bulk operations
- Increase PHP memory limit

### Configuration Management

#### Environment Variables
```bash
# Copy template
cp .env.example .env

# Edit configuration
nano .env

# Validate configuration
php bin/benchmark.php --help
```

#### Production Configuration
```env
# Production settings
REDIS_HOST=redis.production.com
REDIS_PORT=6379
REDIS_PASSWORD=your_secure_password

MEMCACHED_HOST=memcached.production.com
MEMCACHED_PORT=11211

BENCHMARK_ITERATIONS=10000
BENCHMARK_CONCURRENT_CONNECTIONS=50
BENCHMARK_OUTPUT_DIR=/var/log/benchmarks
```

### Monitoring and Logging

#### Log Files
```bash
# Check benchmark logs
tail -f results/benchmark_*.log

# Check application logs
tail -f /var/log/php_errors.log
```

#### Performance Monitoring
```bash
# Monitor system resources
htop
iostat -x 1
netstat -i

# Monitor cache servers
redis-cli info
echo "stats" | nc localhost 11211
```

### Testing

#### Running Tests
```bash
# Run all tests
composer test

# Run specific test
./vendor/bin/phpunit tests/BenchmarkTest.php

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/
```

#### Adding Tests
```php
// tests/BenchmarkTest.php
public function testNewFeature(): void
{
    // Test implementation
    $result = SomeClass::someMethod();
    $this->assertEquals(expected, $result);
}
```

### Deployment

#### Development Environment
```bash
# Clone repository
git clone <repository-url>
cd cache-benchmark

# Install dependencies
composer install

# Setup environment
./setup.sh

# Run tests
composer test
```

#### Production Environment
```bash
# Install system dependencies
sudo apt-get update
sudo apt-get install redis-server memcached php-redis php-memcached

# Deploy application
git clone <repository-url>
cd cache-benchmark
composer install --no-dev

# Configure environment
cp .env.example .env
# Edit .env with production settings

# Set permissions
chmod +x bin/benchmark.php
mkdir -p results
chmod 755 results

# Test deployment
php bin/benchmark.php --iterations=100
```

### Backup and Recovery

#### Configuration Backup
```bash
# Backup configuration
cp .env .env.backup
cp composer.json composer.json.backup

# Restore configuration
cp .env.backup .env
```

#### Results Backup
```bash
# Backup results
tar -czf benchmark_results_$(date +%Y%m%d).tar.gz results/

# Restore results
tar -xzf benchmark_results_20250101.tar.gz
```

### Performance Tuning

#### PHP Configuration
```ini
; php.ini optimizations
memory_limit = 512M
max_execution_time = 300
opcache.enable = 1
opcache.memory_consumption = 128
```

#### Cache Server Tuning
```bash
# Redis optimization
redis-cli config set maxmemory 1gb
redis-cli config set maxmemory-policy allkeys-lru

# Memcached optimization
memcached -m 1024 -c 1024 -t 4
```

### Security Considerations

#### Network Security
```bash
# Restrict access to cache servers
# Redis
redis-cli config set bind 127.0.0.1
redis-cli config set requirepass your_password

# Memcached
memcached -l 127.0.0.1 -U 0
```

#### File Permissions
```bash
# Secure file permissions
chmod 600 .env
chmod 755 bin/
chmod 644 src/**/*.php
```

### Updates and Maintenance

#### Regular Maintenance Tasks
```bash
# Update dependencies
composer update

# Update PHP extensions
pecl update-channels
pecl upgrade redis memcached

# Clean old results
find results/ -name "*.log" -mtime +30 -delete
```

#### Version Compatibility
- **PHP**: 8.0+ (tested with 8.2)
- **Redis**: 6.0+ (tested with 8.2)
- **Memcached**: 1.6+ (tested with 1.6.39)
- **Extensions**: php-redis 5.0+, php-memcached 3.0+

### Support and Resources

#### Documentation
- [README.md](README.md) - User guide
- [QUICKSTART.md](QUICKSTART.md) - Quick start
- [DOCUMENTATION.md](DOCUMENTATION.md) - Implementation details
- [TECHNICAL_SUMMARY.md](TECHNICAL_SUMMARY.md) - Technical overview

#### External Resources
- [Redis Documentation](https://redis.io/documentation)
- [Memcached Documentation](https://memcached.org/documentation)
- [PHP Redis Extension](https://github.com/phpredis/phpredis)
- [PHP Memcached Extension](https://github.com/php-memcached-dev/php-memcached)

#### Getting Help
1. Check logs in `results/` directory
2. Review configuration in `.env`
3. Run tests: `composer test`
4. Check system resources
5. Verify cache server connectivity

### Emergency Procedures

#### System Recovery
```bash
# Restart cache servers
brew services restart redis
brew services restart memcached

# Restart PHP-FPM (if applicable)
sudo systemctl restart php8.2-fpm

# Clear cache data
redis-cli flushall
echo "flush_all" | nc localhost 11211
```

#### Data Recovery
```bash
# Restore from backup
cp .env.backup .env
composer install

# Verify functionality
php bin/benchmark.php --iterations=10
```
