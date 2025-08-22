<?php

/**
 * Simple Benchmark Example
 * 
 * This example demonstrates how to use the Cache Benchmark Tool
 * programmatically in your own PHP code.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CacheBenchmark\Config\Configuration;
use CacheBenchmark\Benchmark\BenchmarkEngine;
use CacheBenchmark\Cache\RedisAdapter;
use CacheBenchmark\Cache\MemcachedAdapter;

// Example 1: Simple Configuration
echo "=== Simple Benchmark Example ===\n\n";

// Create configuration with custom settings
$config = new Configuration();

// Override some settings for this example
$_ENV['BENCHMARK_ITERATIONS'] = 100;
$_ENV['BENCHMARK_CONCURRENT_CONNECTIONS'] = 5;
$_ENV['BENCHMARK_OUTPUT_DIR'] = './examples/results';

// Create benchmark engine
$engine = new BenchmarkEngine($config);

// Run a subset of benchmarks
echo "Running basic operations benchmark...\n";

// Test basic SET operations
$testData = "Hello, World! This is a test string for benchmarking.";
$iterations = 50;

// Redis test
$redis = new RedisAdapter($config->getRedisConfig());
$redisResult = $redis->benchmarkOperation(
    'SET_EXAMPLE',
    fn() => $redis->set('example_key_' . uniqid(), $testData),
    $iterations
);

// Memcached test
$memcached = new MemcachedAdapter($config->getMemcachedConfig());
$memcachedResult = $memcached->benchmarkOperation(
    'SET_EXAMPLE',
    fn() => $memcached->set('example_key_' . uniqid(), $testData),
    $iterations
);

// Display results
echo "\n=== Results ===\n";
echo sprintf(
    "Redis: %.4f ms avg, %d ops/sec, %.2f MB memory\n",
    $redisResult->averageTime,
    $redisResult->throughput,
    $redisResult->memoryUsage
);

echo sprintf(
    "Memcached: %.4f ms avg, %d ops/sec, %.2f MB memory\n",
    $memcachedResult->averageTime,
    $memcachedResult->throughput,
    $memcachedResult->memoryUsage
);

// Determine winner
if ($redisResult->averageTime < $memcachedResult->averageTime) {
    $winner = 'Redis';
    $improvement = (($memcachedResult->averageTime - $redisResult->averageTime) / $memcachedResult->averageTime) * 100;
} else {
    $winner = 'Memcached';
    $improvement = (($redisResult->averageTime - $memcachedResult->averageTime) / $redisResult->averageTime) * 100;
}

echo sprintf("\nWinner: %s (%.2f%% faster)\n", $winner, $improvement);

// Cleanup
$redis->close();
$memcached->close();

echo "\n=== Example Complete ===\n";

// Example 2: Custom Benchmark Function
echo "\n=== Custom Benchmark Function ===\n";

function runCustomBenchmark($cache, $operation, $data, $iterations) {
    $times = [];
    $startTime = microtime(true);
    
    for ($i = 0; $i < $iterations; $i++) {
        $opStart = microtime(true);
        
        switch ($operation) {
            case 'set':
                $cache->set("custom_key_$i", $data);
                break;
            case 'get':
                $cache->get("custom_key_$i");
                break;
            case 'delete':
                $cache->delete("custom_key_$i");
                break;
        }
        
        $times[] = (microtime(true) - $opStart) * 1000;
    }
    
    $totalTime = (microtime(true) - $startTime) * 1000;
    $avgTime = array_sum($times) / count($times);
    $throughput = (int)($iterations / ($totalTime / 1000));
    
    return [
        'operation' => $operation,
        'iterations' => $iterations,
        'total_time' => $totalTime,
        'average_time' => $avgTime,
        'throughput' => $throughput,
        'min_time' => min($times),
        'max_time' => max($times)
    ];
}

// Test custom benchmark
$redis = new RedisAdapter($config->getRedisConfig());
$testData = str_repeat('A', 1024); // 1KB of data

$results = [];
$operations = ['set', 'get', 'delete'];

foreach ($operations as $op) {
    $results[$op] = runCustomBenchmark($redis, $op, $testData, 25);
}

echo "\nCustom Benchmark Results:\n";
foreach ($results as $op => $result) {
    echo sprintf(
        "%s: %.4f ms avg, %d ops/sec\n",
        strtoupper($op),
        $result['average_time'],
        $result['throughput']
    );
}

$redis->close();

echo "\n=== All Examples Complete ===\n";
echo "Check the examples/results/ directory for detailed reports.\n";
