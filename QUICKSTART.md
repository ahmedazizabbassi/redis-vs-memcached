# Quick Start Guide

Get up and running with the Cache Benchmark Tool in 5 minutes!

## Prerequisites

Before you start, make sure you have:

1. **PHP 8.0+** with extensions: `redis`, `memcached`, `json`, `mbstring`
2. **Composer** installed
3. **Redis** server running (default: localhost:6379)
4. **Memcached** server running (default: localhost:11211)

## Installation

### Option 1: Automated Setup (Recommended)

```bash
# Clone the repository
git clone <repository-url>
cd cache-benchmark

# Run the setup script
./setup.sh
```

### Option 2: Manual Setup

```bash
# Install dependencies
composer install

# Copy configuration template
cp env.example .env

# Create results directory
mkdir -p results

# Make script executable
chmod +x bin/benchmark.php
```

## Quick Test

1. **Start your cache servers:**
   ```bash
   # Start Redis
   redis-server
   
   # Start Memcached (in another terminal)
   memcached -d -p 11211
   ```

2. **Run a quick benchmark:**
   ```bash
   php bin/benchmark.php --iterations=100 --concurrent=5
   ```

3. **View results:**
   ```bash
   cat results/benchmark_report_*.txt
   ```

## Sample Output

```
=== Cache Benchmark Tool ===
Starting comprehensive Redis vs Memcached performance comparison

Loading configuration from: .env
Configuration:
  Redis: 127.0.0.1:6379
  Memcached: 127.0.0.1:11211
  Iterations: 100
  Concurrent connections: 5
  Output directory: ./results

Checking prerequisites...
✓ All required PHP extensions are loaded
✓ Redis server is accessible
✓ Memcached server is accessible

Starting benchmarks...
[2024-01-15 14:30:25] benchmark.INFO: Starting comprehensive benchmark suite
[2024-01-15 14:30:26] benchmark.INFO: Running basic operations benchmark
[2024-01-15 14:30:27] benchmark.INFO: Running data size benchmark
[2024-01-15 14:30:28] benchmark.INFO: Running concurrency benchmark
[2024-01-15 14:30:29] benchmark.INFO: Running mixed workload benchmark
[2024-01-15 14:30:30] benchmark.INFO: Running bulk operations benchmark
[2024-01-15 14:30:31] benchmark.INFO: Running expiration benchmark
[2024-01-15 14:30:32] benchmark.INFO: All benchmarks completed

Benchmarks completed in 7.25 seconds
Total tests executed: 48

Generating report...
[2024-01-15 14:30:32] benchmark.INFO: Generating benchmark report

=== QUICK SUMMARY ===
Redis average: 1.2505 ms latency, 800 ops/sec
Memcached average: 1.1800 ms latency, 847 ops/sec

Best performers:
  Lowest latency: GET_small (Memcached) - 0.8500 ms
  Highest throughput: BULK_MGET_500 (Redis) - 1250 ops/sec

Detailed report has been saved to the output directory.

Benchmark completed successfully!
```

## Common Commands

### Basic Usage
```bash
# Default benchmark (1000 iterations, 10 concurrent)
php bin/benchmark.php

# Quick test (100 iterations, 5 concurrent)
php bin/benchmark.php --iterations=100 --concurrent=5

# High-load test (5000 iterations, 50 concurrent)
php bin/benchmark.php --iterations=5000 --concurrent=50
```

### Custom Configuration
```bash
# Use custom config file
php bin/benchmark.php --config=production.env

# Custom output directory
php bin/benchmark.php --output=./my_results

# Get help
php bin/benchmark.php --help
```

## Understanding Results

### Key Metrics

1. **Latency (Response Time)**
   - **Average**: Mean response time
   - **P50/P95/P99**: Percentile response times
   - **Min/Max**: Best and worst case

2. **Throughput**
   - Operations per second
   - Higher is better

3. **Memory Usage**
   - Client-side memory consumption
   - Lower is better

### Sample Results Interpretation

```
Operation: SET_small
Redis: 1.2505 ms avg, 800 ops/sec, 2.45 MB memory
Memcached: 1.1800 ms avg, 847 ops/sec, 1.98 MB memory
Winner (Latency): Memcached (5.64% faster)
Winner (Throughput): Memcached (5.88% higher)
```

**Interpretation**: For small data sets, Memcached is slightly faster and more memory efficient.

## Troubleshooting

### Common Issues

1. **"Failed to connect to Redis"**
   ```bash
   # Check if Redis is running
   redis-cli ping
   
   # Start Redis if needed
   redis-server
   ```

2. **"Failed to connect to Memcached"**
   ```bash
   # Check if Memcached is running
   echo "version" | nc localhost 11211
   
   # Start Memcached if needed
   memcached -d -p 11211
   ```

3. **"Missing required PHP extensions"**
   ```bash
   # Install Redis extension
   sudo pecl install redis
   
   # Install Memcached extension
   sudo pecl install memcached
   ```

4. **"Permission denied"**
   ```bash
   # Make script executable
   chmod +x bin/benchmark.php
   ```

### Debug Mode

Enable detailed logging:
```bash
# Edit .env file
echo "BENCHMARK_LOG_LEVEL=DEBUG" >> .env

# Run benchmark
php bin/benchmark.php
```

## Next Steps

1. **Read the full documentation**: See `README.md` for detailed information
2. **Customize your tests**: Edit `.env` file for your specific needs
3. **Run multiple tests**: Execute benchmarks multiple times for statistical significance
4. **Analyze results**: Review the detailed reports in the `results/` directory

## Support

- Check the troubleshooting section in `README.md`
- Review logs in the `results/` directory
- Create an issue with detailed information

---

**Happy benchmarking! 🚀**
