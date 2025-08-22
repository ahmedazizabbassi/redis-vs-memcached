<?php

namespace CacheBenchmark\Cache;

use Redis;
use CacheBenchmark\Models\BenchmarkResult;
use CacheBenchmark\Utils\Statistics;

class RedisAdapter implements CacheAdapterInterface
{
    private Redis $redis;
    private array $config;
    private int $errors = 0;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    private function connect(): void
    {
        $this->redis = new Redis();
        
        try {
            $this->redis->connect(
                $this->config['host'],
                $this->config['port'],
                $this->config['timeout']
            );

            if (!empty($this->config['password'])) {
                $this->redis->auth($this->config['password']);
            }

            if (isset($this->config['database'])) {
                $this->redis->select($this->config['database']);
            }

            // Set options for better performance
            $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
            $this->redis->setOption(Redis::OPT_PREFIX, 'benchmark:');
            
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to connect to Redis: " . $e->getMessage());
        }
    }

    public function set(string $key, $value, int $ttl = 0): bool
    {
        try {
            if ($ttl > 0) {
                return $this->redis->setex($key, $ttl, $value);
            }
            return $this->redis->set($key, $value);
        } catch (\Exception $e) {
            $this->errors++;
            return false;
        }
    }

    public function get(string $key)
    {
        try {
            return $this->redis->get($key);
        } catch (\Exception $e) {
            $this->errors++;
            return false;
        }
    }

    public function delete(string $key): bool
    {
        try {
            return $this->redis->del($key) > 0;
        } catch (\Exception $e) {
            $this->errors++;
            return false;
        }
    }

    public function exists(string $key): bool
    {
        try {
            return $this->redis->exists($key);
        } catch (\Exception $e) {
            $this->errors++;
            return false;
        }
    }

    public function mset(array $items): bool
    {
        try {
            return $this->redis->mset($items);
        } catch (\Exception $e) {
            $this->errors++;
            return false;
        }
    }

    public function mget(array $keys): array
    {
        try {
            return $this->redis->mget($keys);
        } catch (\Exception $e) {
            $this->errors++;
            return [];
        }
    }

    public function flush(): bool
    {
        try {
            return $this->redis->flushDB();
        } catch (\Exception $e) {
            $this->errors++;
            return false;
        }
    }

    public function ping(): bool
    {
        try {
            return $this->redis->ping() === '+PONG';
        } catch (\Exception $e) {
            $this->errors++;
            return false;
        }
    }

    public function getInfo(): array
    {
        try {
            return $this->redis->info();
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
        if (isset($this->redis)) {
            $this->redis->close();
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
            cacheType: 'Redis',
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
