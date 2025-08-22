<?php

namespace CacheBenchmark\Cache;

use Memcached;
use CacheBenchmark\Models\BenchmarkResult;
use CacheBenchmark\Utils\Statistics;

class MemcachedAdapter implements CacheAdapterInterface
{
    private Memcached $memcached;
    private array $config;
    private int $errors = 0;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    private function connect(): void
    {
        $this->memcached = new Memcached();
        
        try {
            $this->memcached->addServer(
                $this->config['host'],
                $this->config['port'],
                $this->config['weight'] ?? 100
            );

            // Set options for better performance
            $this->memcached->setOption(Memcached::OPT_COMPRESSION, false);
            $this->memcached->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
            $this->memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
            $this->memcached->setOption(Memcached::OPT_TCP_NODELAY, true);
            $this->memcached->setOption(Memcached::OPT_NO_BLOCK, true);
            
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to connect to Memcached: " . $e->getMessage());
        }
    }

    public function set(string $key, $value, int $ttl = 0): bool
    {
        try {
            return $this->memcached->set($key, $value, $ttl);
        } catch (\Exception $e) {
            $this->errors++;
            return false;
        }
    }

    public function get(string $key)
    {
        try {
            return $this->memcached->get($key);
        } catch (\Exception $e) {
            $this->errors++;
            return false;
        }
    }

    public function delete(string $key): bool
    {
        try {
            return $this->memcached->delete($key);
        } catch (\Exception $e) {
            $this->errors++;
            return false;
        }
    }

    public function exists(string $key): bool
    {
        try {
            return $this->memcached->get($key) !== false;
        } catch (\Exception $e) {
            $this->errors++;
            return false;
        }
    }

    public function mset(array $items): bool
    {
        try {
            return $this->memcached->setMulti($items);
        } catch (\Exception $e) {
            $this->errors++;
            return false;
        }
    }

    public function mget(array $keys): array
    {
        try {
            return $this->memcached->getMulti($keys);
        } catch (\Exception $e) {
            $this->errors++;
            return [];
        }
    }

    public function flush(): bool
    {
        try {
            return $this->memcached->flush();
        } catch (\Exception $e) {
            $this->errors++;
            return false;
        }
    }

    public function ping(): bool
    {
        try {
            return $this->memcached->getVersion() !== false;
        } catch (\Exception $e) {
            $this->errors++;
            return false;
        }
    }

    public function getInfo(): array
    {
        try {
            return $this->memcached->getStats();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getErrors(): int
    {
        return $this->errors;
    }

    public function resetErrors(): void
    {
        $this->errors = 0;
    }

    public function close(): void
    {
        if (isset($this->memcached)) {
            $this->memcached->quit();
        }
    }

    public function benchmarkOperation(string $operation, callable $callback, int $iterations): BenchmarkResult
    {
        $times = [];
        $startMemory = Statistics::getMemoryUsage();
        $startTime = microtime(true);
        $this->resetErrors();

        // Warmup
        for ($i = 0; $i < min(100, $iterations / 10); $i++) {
            $callback();
        }

        // Actual benchmark
        for ($i = 0; $i < $iterations; $i++) {
            $opStart = microtime(true);
            $callback();
            $times[] = (microtime(true) - $opStart) * 1000; // Convert to milliseconds
        }

        $totalTime = (microtime(true) - $startTime) * 1000;
        $endMemory = Statistics::getMemoryUsage();
        $percentiles = Statistics::calculatePercentiles($times);

        return new BenchmarkResult(
            operation: $operation,
            cacheType: 'Memcached',
            iterations: $iterations,
            totalTime: $totalTime,
            averageTime: array_sum($times) / count($times),
            minTime: min($times),
            maxTime: max($times),
            p50Time: $percentiles[50] ?? 0,
            p95Time: $percentiles[95] ?? 0,
            p99Time: $percentiles[99] ?? 0,
            throughput: Statistics::calculateThroughput($iterations, $totalTime / 1000),
            memoryUsage: $endMemory - $startMemory,
            errors: $this->getErrors(),
            percentiles: $percentiles
        );
    }
}
