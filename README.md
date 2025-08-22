# Cache Benchmark Tool

A comprehensive benchmarking suite for comparing Redis vs Memcached performance in PHP applications. This tool provides detailed analysis of latency, throughput, memory efficiency, and scalability under various real-world scenarios.

## Features

### 🎯 **Comprehensive Testing**
- **Basic Operations**: GET/SET operations with different data sizes
- **Data Size Patterns**: Tests from 64 bytes to 1MB to understand size impact
- **Concurrent Connections**: Tests with 1, 5, 10, 25, 50, and 100 concurrent connections
- **Mixed Workloads**: 80% reads / 20% writes (typical cache pattern)
- **Bulk Operations**: Multi-get/set operations with various batch sizes
- **Expiration Handling**: TTL-based operations performance

### 📊 **Detailed Metrics**
- **Latency**: Average, min, max, and percentile times (P50, P95, P99)
- **Throughput**: Operations per second under different loads
- **Memory Efficiency**: Memory usage tracking for both client and server
- **Error Rates**: Connection failures and operation errors
- **Statistical Analysis**: Standard deviation, coefficient of variation

### 🔧 **Flexible Configuration**
- Environment-based configuration
- CLI options for quick parameter overrides
- Customizable test parameters
- Multiple output formats

## Installation

### Option 1: Docker (Recommended)

**Prerequisites:**
- Docker 20.10+ with Docker Compose 2.0+

**Quick Setup:**
```bash
# Clone and run
git clone <repository-url>
cd cache-benchmark
make up

# Or use docker-compose directly
docker-compose --profile benchmark up --build
```

**Docker Features:**
- ✅ Pre-configured PHP environment with all extensions
- ✅ Redis and Memcached servers included
- ✅ Web interface for viewing results
- ✅ Development environment with hot reload
- ✅ Consistent environment across all systems

### Option 2: Local Installation

**Prerequisites:**

1. **PHP 8.0+** with the following extensions:
   - `ext-redis`
   - `ext-memcached`
   - `ext-json`
   - `ext-mbstring`

2. **Redis Server** (running on default port 6379)
3. **Memcached Server** (running on default port 11211)

**Setup:**

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd cache-benchmark
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Configure the environment:**
   ```bash
   cp .env.example .env
   # Edit .env with your Redis and Memcached connection details
   ```

4. **Make the benchmark script executable:**
   ```bash
   chmod +x bin/benchmark.php
   ```

## Usage

### Docker Usage

**Quick Commands:**
```bash
# Run full benchmark suite
make benchmark

# Start development environment
make dev

# Start with web interface
make web

# Quick test
make quick

# View logs
make logs

# Access shell
make shell
```

**Docker Compose Commands:**
```bash
# Production benchmark
docker-compose --profile benchmark up --build

# Development environment
docker-compose --profile dev up --build

# With web interface
docker-compose --profile benchmark --profile web up --build

# Run custom benchmark
docker-compose exec app php bin/benchmark.php --iterations=1000 --concurrent=20
```

### Local Usage

**Basic Usage:**

Run the benchmark with default settings:
```bash
php bin/benchmark.php
```

**Advanced Usage:**

Run with custom parameters:
```bash
# High-load testing
php bin/benchmark.php --iterations=5000 --concurrent=50

# Custom configuration
php bin/benchmark.php --config=production.env --output=./benchmark_results

# Quick test
php bin/benchmark.php --iterations=100 --concurrent=5
```

### CLI Options

| Option | Description | Default |
|--------|-------------|---------|
| `--config=FILE` | Configuration file | `.env` |
| `--iterations=N` | Number of iterations per test | `1000` |
| `--concurrent=N` | Number of concurrent connections | `10` |
| `--output=DIR` | Output directory for reports | `./results` |
| `--help` | Show help message | - |

## Configuration

### Environment Variables

Create a `.env` file based on `env.example`:

```env
# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0
REDIS_TIMEOUT=5.0

# Memcached Configuration
MEMCACHED_HOST=127.0.0.1
MEMCACHED_PORT=11211
MEMCACHED_WEIGHT=100

# Benchmark Configuration
BENCHMARK_ITERATIONS=1000
BENCHMARK_CONCURRENT_CONNECTIONS=10
BENCHMARK_WARMUP_ITERATIONS=100
BENCHMARK_OUTPUT_DIR=./results
BENCHMARK_LOG_LEVEL=INFO

# Data Patterns
SMALL_VALUE_SIZE=1024
MEDIUM_VALUE_SIZE=10240
LARGE_VALUE_SIZE=102400
```

## Test Scenarios

### 1. Basic Operations
Tests fundamental GET/SET operations with three data sizes:
- **Small**: 1KB (session data, user preferences)
- **Medium**: 10KB (serialized objects, query results)
- **Large**: 100KB (cached HTML fragments, images)

### 2. Data Size Impact
Tests performance across a range of data sizes:
- 64 bytes to 1MB
- Helps identify optimal data size thresholds
- Reveals serialization overhead

### 3. Concurrency Testing
Tests performance under increasing load:
- 1, 5, 10, 25, 50, 100 concurrent connections
- Measures scalability characteristics
- Identifies connection pool optimization opportunities

### 4. Mixed Workloads
Simulates real-world cache usage patterns:
- 80% read operations
- 20% write operations
- Tests cache hit/miss scenarios

### 5. Bulk Operations
Tests multi-get/set operations:
- Batch sizes: 10, 50, 100, 500 items
- Measures network efficiency
- Tests pipeline performance

### 6. Expiration Handling
Tests TTL-based operations:
- Performance impact of expiration
- Memory management efficiency

## Output and Reports

### Generated Files

The benchmark generates several output files in the configured output directory:

1. **Benchmark Report** (`benchmark_report_YYYY-MM-DD_HH-MM-SS.txt`)
   - Comprehensive performance analysis
   - Detailed comparison between Redis and Memcached
   - Statistical summaries and recommendations

2. **Log File** (`benchmark.log`)
   - Detailed execution logs
   - Error tracking and debugging information

### Report Structure

```
=== CACHE BENCHMARK SUMMARY REPORT ===
Generated: 2024-01-15 14:30:25
Total Tests: 48

=== DETAILED RESULTS ===
Operation: SET_small (Redis)
  Iterations: 1000
  Total Time: 1250.50 ms
  Average Time: 1.2505 ms
  Min Time: 0.8500 ms
  Max Time: 5.2000 ms
  P50 Time: 1.1500 ms
  P95 Time: 2.1000 ms
  P99 Time: 3.5000 ms
  Throughput: 800 ops/sec
  Memory Usage: 2.45 MB
  Errors: 0

=== PERFORMANCE COMPARISON ===
Operation: SET_small
Redis: 1.2505 ms avg, 800 ops/sec, 2.45 MB memory
Memcached: 1.1800 ms avg, 847 ops/sec, 1.98 MB memory
Winner (Latency): Memcached (5.64% faster)
Winner (Throughput): Memcached (5.88% higher)
```

## Performance Analysis

### Key Metrics Explained

1. **Latency (Response Time)**
   - **Average**: Mean response time across all operations
   - **Percentiles**: P50 (median), P95, P99 show tail latency
   - **Min/Max**: Best and worst case performance

2. **Throughput**
   - Operations per second under sustained load
   - Indicates system capacity and efficiency

3. **Memory Usage**
   - Client-side memory consumption
   - Helps identify memory leaks or inefficiencies

4. **Error Rates**
   - Connection failures and operation errors
   - Indicates system stability and reliability

### Interpreting Results

#### When Redis Performs Better
- **Complex data structures** (lists, sets, hashes)
- **Atomic operations** and transactions
- **Pub/sub messaging** requirements
- **Data persistence** needs

#### When Memcached Performs Better
- **Simple key-value operations**
- **High-throughput scenarios**
- **Memory-only caching**
- **Horizontal scaling** with consistent hashing

## Best Practices

### Running Benchmarks

1. **Isolate the Environment**
   - Run on dedicated hardware/VM
   - Close other applications
   - Use consistent network conditions

2. **Warm Up the System**
   - Run a few iterations before actual testing
   - Ensure caches are populated
   - Stabilize system resources

3. **Multiple Test Runs**
   - Run benchmarks multiple times
   - Calculate averages and standard deviations
   - Account for system variance

4. **Monitor System Resources**
   - CPU usage during tests
   - Memory consumption
   - Network I/O
   - Disk activity

### Configuration Optimization

1. **Redis Optimization**
   ```conf
   # redis.conf
   maxmemory-policy allkeys-lru
   save ""
   appendonly no
   ```

2. **Memcached Optimization**
   ```bash
   memcached -m 1024 -t 4 -c 1024
   ```

3. **PHP Configuration**
   ```ini
   ; php.ini
   memory_limit = 512M
   max_execution_time = 300
   ```

## Troubleshooting

### Common Issues

1. **Connection Failures**
   - Verify Redis/Memcached servers are running
   - Check firewall settings
   - Validate connection parameters

2. **Memory Issues**
   - Increase PHP memory limit
   - Monitor system memory usage
   - Check for memory leaks

3. **Performance Anomalies**
   - Run with fewer iterations first
   - Check system load
   - Verify network latency

### Debug Mode

Enable detailed logging by setting:
```env
BENCHMARK_LOG_LEVEL=DEBUG
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For issues and questions:
1. Check the troubleshooting section
2. Review the logs in the output directory
3. Create an issue with detailed information

---

**Note**: This benchmarking tool is designed for development and testing environments. Always validate results in your specific production environment before making architectural decisions.
