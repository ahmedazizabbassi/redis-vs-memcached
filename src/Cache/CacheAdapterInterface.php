<?php

namespace CacheBenchmark\Cache;

use CacheBenchmark\Models\BenchmarkResult;

interface CacheAdapterInterface
{
    public function set(string $key, $value, int $ttl = 0): bool;
    public function get(string $key);
    public function delete(string $key): bool;
    public function exists(string $key): bool;
    public function mset(array $items): bool;
    public function mget(array $keys): array;
    public function flush(): bool;
    public function ping(): bool;
    public function getInfo(): array;
    public function getErrors(): int;
    public function resetErrors(): void;
    public function close(): void;
    public function benchmarkOperation(string $operation, callable $callback, int $iterations): BenchmarkResult;
}
