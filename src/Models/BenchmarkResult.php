<?php

namespace CacheBenchmark\Models;

class BenchmarkResult
{
    public function __construct(
        public string $operation,
        public string $cacheType,
        public int $iterations,
        public float $totalTime,
        public float $averageTime,
        public float $minTime,
        public float $maxTime,
        public float $p50Time,
        public float $p95Time,
        public float $p99Time,
        public int $throughput,
        public float $memoryUsage,
        public int $errors,
        public array $percentiles = [],
        public array $metadata = []
    ) {}

    public function toArray(): array
    {
        return [
            'operation' => $this->operation,
            'cache_type' => $this->cacheType,
            'iterations' => $this->iterations,
            'total_time' => $this->totalTime,
            'average_time' => $this->averageTime,
            'min_time' => $this->minTime,
            'max_time' => $this->maxTime,
            'p50_time' => $this->p50Time,
            'p95_time' => $this->p95Time,
            'p99_time' => $this->p99Time,
            'throughput' => $this->throughput,
            'memory_usage' => $this->memoryUsage,
            'errors' => $this->errors,
            'percentiles' => $this->percentiles,
            'metadata' => $this->metadata,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}
