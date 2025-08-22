<?php

namespace CacheBenchmark\Benchmark;

use CacheBenchmark\Cache\RedisAdapter;
use CacheBenchmark\Cache\MemcachedAdapter;
use CacheBenchmark\Config\Configuration;
use CacheBenchmark\Models\BenchmarkResult;
use CacheBenchmark\Utils\Statistics;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class BenchmarkEngine
{
    private Configuration $config;
    private Logger $logger;
    private RedisAdapter $redis;
    private MemcachedAdapter $memcached;
    private array $results = [];

    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->setupLogger();
        $this->setupConnections();
    }

    private function setupLogger(): void
    {
        $this->logger = new Logger('benchmark');
        $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));
        
        if (!is_dir($this->config->getBenchmarkConfig()['output_dir'])) {
            mkdir($this->config->getBenchmarkConfig()['output_dir'], 0755, true);
        }
        
        $this->logger->pushHandler(
            new StreamHandler(
                $this->config->getBenchmarkConfig()['output_dir'] . '/benchmark.log',
                Logger::DEBUG
            )
        );
    }

    private function setupConnections(): void
    {
        try {
            $this->redis = new RedisAdapter($this->config->getRedisConfig());
            $this->logger->info('Redis connection established');
        } catch (\Exception $e) {
            $this->logger->error('Failed to connect to Redis: ' . $e->getMessage());
            throw $e;
        }

        try {
            $this->memcached = new MemcachedAdapter($this->config->getMemcachedConfig());
            $this->logger->info('Memcached connection established');
        } catch (\Exception $e) {
            $this->logger->error('Failed to connect to Memcached: ' . $e->getMessage());
            throw $e;
        }
    }

    public function runAllBenchmarks(): array
    {
        $this->logger->info('Starting comprehensive benchmark suite');

        // Test basic operations
        $this->runBasicOperationsBenchmark();
        
        // Test data size patterns
        $this->runDataSizeBenchmark();
        
        // Test concurrent connections
        $this->runConcurrencyBenchmark();
        
        // Test mixed workloads
        $this->runMixedWorkloadBenchmark();
        
        // Test bulk operations
        $this->runBulkOperationsBenchmark();
        
        // Test expiration handling
        $this->runExpirationBenchmark();

        $this->logger->info('All benchmarks completed');
        return $this->results;
    }

    private function runBasicOperationsBenchmark(): void
    {
        $this->logger->info('Running basic operations benchmark');
        $iterations = $this->config->getBenchmarkConfig()['iterations'];
        $dataPatterns = $this->config->getDataPatterns();

        foreach (['small', 'medium', 'large'] as $size) {
            $dataSize = $dataPatterns[$size . '_value_size'];
            $testData = Statistics::generateTestData($dataSize);

            // Redis SET operations
            $result = $this->redis->benchmarkOperation(
                "SET_{$size}",
                fn() => $this->redis->set("test_key_" . uniqid(), $testData),
                $iterations
            );
            $this->results[] = $result;

            // Redis GET operations
            $key = "test_key_" . uniqid();
            $this->redis->set($key, $testData);
            $result = $this->redis->benchmarkOperation(
                "GET_{$size}",
                fn() => $this->redis->get($key),
                $iterations
            );
            $this->results[] = $result;

            // Memcached SET operations
            $result = $this->memcached->benchmarkOperation(
                "SET_{$size}",
                fn() => $this->memcached->set("test_key_" . uniqid(), $testData),
                $iterations
            );
            $this->results[] = $result;

            // Memcached GET operations
            $key = "test_key_" . uniqid();
            $this->memcached->set($key, $testData);
            $result = $this->memcached->benchmarkOperation(
                "GET_{$size}",
                fn() => $this->memcached->get($key),
                $iterations
            );
            $this->results[] = $result;
        }
    }

    private function runDataSizeBenchmark(): void
    {
        $this->logger->info('Running data size benchmark');
        $iterations = $this->config->getBenchmarkConfig()['iterations'];
        $sizes = [64, 256, 1024, 4096, 16384, 65536, 262144, 1048576]; // 64B to 1MB

        foreach ($sizes as $size) {
            $testData = Statistics::generateTestData($size);

            // Redis
            $result = $this->redis->benchmarkOperation(
                "SET_SIZE_{$size}",
                fn() => $this->redis->set("size_test_" . uniqid(), $testData),
                $iterations
            );
            $this->results[] = $result;

            // Memcached
            $result = $this->memcached->benchmarkOperation(
                "SET_SIZE_{$size}",
                fn() => $this->memcached->set("size_test_" . uniqid(), $testData),
                $iterations
            );
            $this->results[] = $result;
        }
    }

    private function runConcurrencyBenchmark(): void
    {
        $this->logger->info('Running concurrency benchmark');
        $concurrencyLevels = [1, 5, 10, 25, 50, 100];
        $iterations = $this->config->getBenchmarkConfig()['iterations'];

        foreach ($concurrencyLevels as $concurrency) {
            $this->runConcurrentTest($concurrency, $iterations);
        }
    }

    private function runConcurrentTest(int $concurrency, int $iterations): void
    {
        $testData = Statistics::generateTestData(1024);
        $operationsPerThread = (int)($iterations / $concurrency);

        // Redis concurrent test
        $startTime = microtime(true);
        $threads = [];
        
        for ($i = 0; $i < $concurrency; $i++) {
            $threads[] = function() use ($testData, $operationsPerThread) {
                for ($j = 0; $j < $operationsPerThread; $j++) {
                    $this->redis->set("concurrent_test_" . uniqid(), $testData);
                }
            };
        }

        // Simulate concurrent execution (in real scenario, you'd use actual threads)
        foreach ($threads as $thread) {
            $thread();
        }

        $totalTime = (microtime(true) - $startTime) * 1000;
        $this->results[] = new BenchmarkResult(
            operation: "CONCURRENT_SET_{$concurrency}",
            cacheType: 'Redis',
            iterations: $iterations,
            totalTime: $totalTime,
            averageTime: $totalTime / $iterations,
            minTime: 0,
            maxTime: 0,
            p50Time: 0,
            p95Time: 0,
            p99Time: 0,
            throughput: Statistics::calculateThroughput($iterations, $totalTime / 1000),
            memoryUsage: Statistics::getMemoryUsage(),
            errors: 0
        );
    }

    private function runMixedWorkloadBenchmark(): void
    {
        $this->logger->info('Running mixed workload benchmark');
        $iterations = $this->config->getBenchmarkConfig()['iterations'];
        $workload = Statistics::generateMixedWorkload($iterations);
        $testData = Statistics::generateTestData(1024);

        // Redis mixed workload
        $result = $this->redis->benchmarkOperation(
            'MIXED_WORKLOAD',
            function() use ($workload, $testData) {
                static $index = 0;
                if ($index >= count($workload)) $index = 0;
                
                $operation = $workload[$index++];
                $key = "mixed_test_" . uniqid();
                
                if ($operation === 'write') {
                    $this->redis->set($key, $testData);
                } else {
                    $this->redis->get($key);
                }
            },
            $iterations
        );
        $this->results[] = $result;

        // Memcached mixed workload
        $result = $this->memcached->benchmarkOperation(
            'MIXED_WORKLOAD',
            function() use ($workload, $testData) {
                static $index = 0;
                if ($index >= count($workload)) $index = 0;
                
                $operation = $workload[$index++];
                $key = "mixed_test_" . uniqid();
                
                if ($operation === 'write') {
                    $this->memcached->set($key, $testData);
                } else {
                    $this->memcached->get($key);
                }
            },
            $iterations
        );
        $this->results[] = $result;
    }

    private function runBulkOperationsBenchmark(): void
    {
        $this->logger->info('Running bulk operations benchmark');
        $iterations = $this->config->getBenchmarkConfig()['iterations'];
        $batchSizes = [10, 50, 100, 500];

        foreach ($batchSizes as $batchSize) {
            $testData = Statistics::generateTestData(1024);
            $items = [];
            $keys = [];

            for ($i = 0; $i < $batchSize; $i++) {
                $key = "bulk_test_" . uniqid();
                $items[$key] = $testData;
                $keys[] = $key;
            }

            // Redis bulk operations
            $result = $this->redis->benchmarkOperation(
                "BULK_MSET_{$batchSize}",
                fn() => $this->redis->mset($items),
                $iterations
            );
            $this->results[] = $result;

            $result = $this->redis->benchmarkOperation(
                "BULK_MGET_{$batchSize}",
                fn() => $this->redis->mget($keys),
                $iterations
            );
            $this->results[] = $result;

            // Memcached bulk operations
            $result = $this->memcached->benchmarkOperation(
                "BULK_MSET_{$batchSize}",
                fn() => $this->memcached->mset($items),
                $iterations
            );
            $this->results[] = $result;

            $result = $this->memcached->benchmarkOperation(
                "BULK_MGET_{$batchSize}",
                fn() => $this->memcached->mget($keys),
                $iterations
            );
            $this->results[] = $result;
        }
    }

    private function runExpirationBenchmark(): void
    {
        $this->logger->info('Running expiration benchmark');
        $iterations = $this->config->getBenchmarkConfig()['iterations'];
        $testData = Statistics::generateTestData(1024);

        // Redis with TTL
        $result = $this->redis->benchmarkOperation(
            'SET_WITH_TTL',
            fn() => $this->redis->set("ttl_test_" . uniqid(), $testData, 60),
            $iterations
        );
        $this->results[] = $result;

        // Memcached with TTL
        $result = $this->memcached->benchmarkOperation(
            'SET_WITH_TTL',
            fn() => $this->memcached->set("ttl_test_" . uniqid(), $testData, 60),
            $iterations
        );
        $this->results[] = $result;
    }

    public function generateReport(): string
    {
        $this->logger->info('Generating benchmark report');
        
        $report = $this->generateSummaryReport();
        $report .= $this->generateDetailedReport();
        $report .= $this->generateComparisonReport();

        $outputFile = $this->config->getBenchmarkConfig()['output_dir'] . '/benchmark_report_' . date('Y-m-d_H-i-s') . '.txt';
        file_put_contents($outputFile, $report);
        
        $this->logger->info("Report saved to: {$outputFile}");
        return $report;
    }

    private function generateSummaryReport(): string
    {
        $report = "=== CACHE BENCHMARK SUMMARY REPORT ===\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $report .= "Total Tests: " . count($this->results) . "\n\n";

        // Group results by cache type
        $redisResults = array_filter($this->results, fn($r) => $r->cacheType === 'Redis');
        $memcachedResults = array_filter($this->results, fn($r) => $r->cacheType === 'Memcached');

        $report .= "Redis Tests: " . count($redisResults) . "\n";
        $report .= "Memcached Tests: " . count($memcachedResults) . "\n\n";

        return $report;
    }

    private function generateDetailedReport(): string
    {
        $report = "=== DETAILED RESULTS ===\n\n";

        foreach ($this->results as $result) {
            $report .= sprintf(
                "Operation: %s (%s)\n",
                $result->operation,
                $result->cacheType
            );
            $report .= sprintf("  Iterations: %d\n", $result->iterations);
            $report .= sprintf("  Total Time: %.2f ms\n", $result->totalTime);
            $report .= sprintf("  Average Time: %.4f ms\n", $result->averageTime);
            $report .= sprintf("  Min Time: %.4f ms\n", $result->minTime);
            $report .= sprintf("  Max Time: %.4f ms\n", $result->maxTime);
            $report .= sprintf("  P50 Time: %.4f ms\n", $result->p50Time);
            $report .= sprintf("  P95 Time: %.4f ms\n", $result->p95Time);
            $report .= sprintf("  P99 Time: %.4f ms\n", $result->p99Time);
            $report .= sprintf("  Throughput: %d ops/sec\n", $result->throughput);
            $report .= sprintf("  Memory Usage: %.2f MB\n", $result->memoryUsage);
            $report .= sprintf("  Errors: %d\n", $result->errors);
            $report .= "\n";
        }

        return $report;
    }

    private function generateComparisonReport(): string
    {
        $report = "=== PERFORMANCE COMPARISON ===\n\n";

        // Group by operation
        $operations = [];
        foreach ($this->results as $result) {
            $operations[$result->operation][] = $result;
        }

        foreach ($operations as $operation => $results) {
            if (count($results) < 2) continue;

            $report .= "Operation: {$operation}\n";
            $report .= str_repeat("-", strlen($operation) + 10) . "\n";

            foreach ($results as $result) {
                $report .= sprintf(
                    "%s: %.4f ms avg, %d ops/sec, %.2f MB memory\n",
                    $result->cacheType,
                    $result->averageTime,
                    $result->throughput,
                    $result->memoryUsage
                );
            }

            // Find winner
            $fastest = $results[array_search(min(array_map(fn($r) => $r->averageTime, $results)), array_map(fn($r) => $r->averageTime, $results))];
            $highestThroughput = $results[array_search(max(array_map(fn($r) => $r->throughput, $results)), array_map(fn($r) => $r->throughput, $results))];
            
            $report .= sprintf(
                "Winner (Latency): %s (%.2f%% faster)\n",
                $fastest->cacheType,
                $this->calculateImprovement($results, $fastest, 'averageTime')
            );
            
            $report .= sprintf(
                "Winner (Throughput): %s (%.2f%% higher)\n",
                $highestThroughput->cacheType,
                $this->calculateImprovement($results, $highestThroughput, 'throughput')
            );
            
            $report .= "\n";
        }

        return $report;
    }

    private function calculateImprovement(array $results, BenchmarkResult $winner, string $metric): float
    {
        $others = array_filter($results, fn($r) => $r !== $winner);
        if (empty($others)) return 0;

        $otherAvg = array_sum(array_map(fn($r) => $r->$metric, $others)) / count($others);
        $winnerValue = $winner->$metric;

        if ($metric === 'throughput') {
            return (($winnerValue - $otherAvg) / $otherAvg) * 100;
        } else {
            return (($otherAvg - $winnerValue) / $otherAvg) * 100;
        }
    }

    public function cleanup(): void
    {
        $this->redis->flush();
        $this->memcached->flush();
        $this->redis->close();
        $this->memcached->close();
    }
}
