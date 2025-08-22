<?php

namespace CacheBenchmark\Config;

use Dotenv\Dotenv;

class Configuration
{
    private array $config;

    public function __construct(string $envFile = '.env')
    {
        if (file_exists($envFile)) {
            $dotenv = Dotenv::createImmutable(dirname($envFile));
            $dotenv->load();
        }

        $this->config = [
            'redis' => [
                'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                'port' => (int)($_ENV['REDIS_PORT'] ?? 6379),
                'password' => $_ENV['REDIS_PASSWORD'] ?? null,
                'database' => (int)($_ENV['REDIS_DATABASE'] ?? 0),
                'timeout' => (float)($_ENV['REDIS_TIMEOUT'] ?? 5.0),
            ],
            'memcached' => [
                'host' => $_ENV['MEMCACHED_HOST'] ?? '127.0.0.1',
                'port' => (int)($_ENV['MEMCACHED_PORT'] ?? 11211),
                'weight' => (int)($_ENV['MEMCACHED_WEIGHT'] ?? 100),
            ],
            'benchmark' => [
                'iterations' => (int)($_ENV['BENCHMARK_ITERATIONS'] ?? 1000),
                'concurrent_connections' => (int)($_ENV['BENCHMARK_CONCURRENT_CONNECTIONS'] ?? 10),
                'warmup_iterations' => (int)($_ENV['BENCHMARK_WARMUP_ITERATIONS'] ?? 100),
                'output_dir' => $_ENV['BENCHMARK_OUTPUT_DIR'] ?? './results',
                'log_level' => $_ENV['BENCHMARK_LOG_LEVEL'] ?? 'INFO',
            ],
            'data_patterns' => [
                'small_value_size' => (int)($_ENV['SMALL_VALUE_SIZE'] ?? 1024),
                'medium_value_size' => (int)($_ENV['MEDIUM_VALUE_SIZE'] ?? 10240),
                'large_value_size' => (int)($_ENV['LARGE_VALUE_SIZE'] ?? 102400),
            ],
        ];
    }

    public function getRedisConfig(): array
    {
        return $this->config['redis'];
    }

    public function getMemcachedConfig(): array
    {
        return $this->config['memcached'];
    }

    public function getBenchmarkConfig(): array
    {
        return $this->config['benchmark'];
    }

    public function getDataPatterns(): array
    {
        return $this->config['data_patterns'];
    }

    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}
