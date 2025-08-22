#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CacheBenchmark\Config\Configuration;
use CacheBenchmark\Benchmark\BenchmarkEngine;

/**
 * Cache Benchmark Tool
 * 
 * Comprehensive benchmarking tool for Redis vs Memcached performance comparison
 * 
 * Usage:
 *   php bin/benchmark.php [options]
 * 
 * Options:
 *   --config=FILE     Configuration file (default: .env)
 *   --iterations=N    Number of iterations per test (default: 1000)
 *   --concurrent=N    Number of concurrent connections (default: 10)
 *   --output=DIR      Output directory for reports (default: ./results)
 *   --help           Show this help message
 */

class BenchmarkCLI
{
    private array $options = [];

    public function __construct(array $argv)
    {
        $this->parseOptions($argv);
    }

    private function parseOptions(array $argv): void
    {
        foreach ($argv as $arg) {
            if (strpos($arg, '--') === 0) {
                $parts = explode('=', $arg, 2);
                $key = substr($parts[0], 2);
                $value = $parts[1] ?? true;
                
                switch ($key) {
                    case 'config':
                        $this->options['config'] = $value;
                        break;
                    case 'iterations':
                        $this->options['iterations'] = (int)$value;
                        break;
                    case 'concurrent':
                        $this->options['concurrent'] = (int)$value;
                        break;
                    case 'output':
                        $this->options['output'] = $value;
                        break;
                    case 'help':
                        $this->showHelp();
                        exit(0);
                    default:
                        echo "Unknown option: --{$key}\n";
                        $this->showHelp();
                        exit(1);
                }
            }
        }
    }

    private function showHelp(): void
    {
        echo "Cache Benchmark Tool\n\n";
        echo "Usage: php bin/benchmark.php [options]\n\n";
        echo "Options:\n";
        echo "  --config=FILE     Configuration file (default: .env)\n";
        echo "  --iterations=N    Number of iterations per test (default: 1000)\n";
        echo "  --concurrent=N    Number of concurrent connections (default: 10)\n";
        echo "  --output=DIR      Output directory for reports (default: ./results)\n";
        echo "  --help           Show this help message\n\n";
        echo "Examples:\n";
        echo "  php bin/benchmark.php\n";
        echo "  php bin/benchmark.php --iterations=5000 --concurrent=50\n";
        echo "  php bin/benchmark.php --config=production.env --output=./benchmark_results\n";
    }

    public function run(): int
    {
        try {
            echo "=== Cache Benchmark Tool ===\n";
            echo "Starting comprehensive Redis vs Memcached performance comparison\n\n";

            // Load configuration
            $configFile = $this->options['config'] ?? '.env';
            echo "Loading configuration from: {$configFile}\n";
            
            $config = new Configuration($configFile);
            
            // Override config with CLI options
            if (isset($this->options['iterations'])) {
                $_ENV['BENCHMARK_ITERATIONS'] = $this->options['iterations'];
            }
            if (isset($this->options['concurrent'])) {
                $_ENV['BENCHMARK_CONCURRENT_CONNECTIONS'] = $this->options['concurrent'];
            }
            if (isset($this->options['output'])) {
                $_ENV['BENCHMARK_OUTPUT_DIR'] = $this->options['output'];
            }

            // Display configuration
            $this->displayConfiguration($config);

            // Check prerequisites
            $this->checkPrerequisites();

            // Run benchmarks
            echo "\nStarting benchmarks...\n";
            $startTime = microtime(true);
            
            $engine = new BenchmarkEngine($config);
            $results = $engine->runAllBenchmarks();
            
            $totalTime = microtime(true) - $startTime;
            
            echo "\nBenchmarks completed in " . number_format($totalTime, 2) . " seconds\n";
            echo "Total tests executed: " . count($results) . "\n\n";

            // Generate report
            echo "Generating report...\n";
            $report = $engine->generateReport();
            
            // Display summary
            $this->displaySummary($results);
            
            // Cleanup
            $engine->cleanup();
            
            echo "\nBenchmark completed successfully!\n";
            return 0;

        } catch (\Exception $e) {
            echo "\nError: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            return 1;
        }
    }

    private function displayConfiguration(Configuration $config): void
    {
        echo "Configuration:\n";
        echo "  Redis: " . $config->getRedisConfig()['host'] . ":" . $config->getRedisConfig()['port'] . "\n";
        echo "  Memcached: " . $config->getMemcachedConfig()['host'] . ":" . $config->getMemcachedConfig()['port'] . "\n";
        echo "  Iterations: " . $config->getBenchmarkConfig()['iterations'] . "\n";
        echo "  Concurrent connections: " . $config->getBenchmarkConfig()['concurrent_connections'] . "\n";
        echo "  Output directory: " . $config->getBenchmarkConfig()['output_dir'] . "\n";
        echo "  Data patterns:\n";
        foreach ($config->getDataPatterns() as $pattern => $size) {
            echo "    {$pattern}: " . number_format($size) . " bytes\n";
        }
        echo "\n";
    }

    private function checkPrerequisites(): void
    {
        echo "Checking prerequisites...\n";
        
        $extensions = ['redis', 'memcached', 'json', 'mbstring'];
        $missing = [];
        
        foreach ($extensions as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        
        if (!empty($missing)) {
            throw new \RuntimeException("Missing required PHP extensions: " . implode(', ', $missing));
        }
        
        echo "✓ All required PHP extensions are loaded\n";
        
        // Check if Redis and Memcached are accessible
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379, 1);
            $redis->ping();
            $redis->close();
            echo "✓ Redis server is accessible\n";
        } catch (\Exception $e) {
            echo "⚠ Warning: Redis server may not be accessible: " . $e->getMessage() . "\n";
        }
        
        try {
            $memcached = new Memcached();
            $memcached->addServer('127.0.0.1', 11211);
            $memcached->getVersion();
            $memcached->quit();
            echo "✓ Memcached server is accessible\n";
        } catch (\Exception $e) {
            echo "⚠ Warning: Memcached server may not be accessible: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    private function displaySummary(array $results): void
    {
        echo "=== QUICK SUMMARY ===\n";
        
        // Group by cache type
        $redisResults = array_filter($results, fn($r) => $r->cacheType === 'Redis');
        $memcachedResults = array_filter($results, fn($r) => $r->cacheType === 'Memcached');
        
        if (!empty($redisResults)) {
            $avgLatency = array_sum(array_map(fn($r) => $r->averageTime, $redisResults)) / count($redisResults);
            $avgThroughput = array_sum(array_map(fn($r) => $r->throughput, $redisResults)) / count($redisResults);
            echo "Redis average: " . number_format($avgLatency, 4) . " ms latency, " . number_format($avgThroughput) . " ops/sec\n";
        }
        
        if (!empty($memcachedResults)) {
            $avgLatency = array_sum(array_map(fn($r) => $r->averageTime, $memcachedResults)) / count($memcachedResults);
            $avgThroughput = array_sum(array_map(fn($r) => $r->throughput, $memcachedResults)) / count($memcachedResults);
            echo "Memcached average: " . number_format($avgLatency, 4) . " ms latency, " . number_format($avgThroughput) . " ops/sec\n";
        }
        
        // Find best performers
        if (!empty($results)) {
            $fastest = min($results, fn($a, $b) => $a->averageTime <=> $b->averageTime);
            $highestThroughput = max($results, fn($a, $b) => $a->throughput <=> $b->throughput);
            
            echo "\nBest performers:\n";
            echo "  Lowest latency: {$fastest->operation} ({$fastest->cacheType}) - " . number_format($fastest->averageTime, 4) . " ms\n";
            echo "  Highest throughput: {$highestThroughput->operation} ({$highestThroughput->cacheType}) - " . number_format($highestThroughput->throughput) . " ops/sec\n";
        }
        
        echo "\nDetailed report has been saved to the output directory.\n";
    }
}

// Run the CLI application
if (php_sapi_name() === 'cli') {
    $cli = new BenchmarkCLI($argv);
    exit($cli->run());
}
