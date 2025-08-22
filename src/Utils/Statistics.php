<?php

namespace CacheBenchmark\Utils;

class Statistics
{
    /**
     * Calculate percentiles from an array of values
     */
    public static function calculatePercentiles(array $values, array $percentiles = [50, 95, 99]): array
    {
        if (empty($values)) {
            return [];
        }

        sort($values);
        $result = [];

        foreach ($percentiles as $percentile) {
            $index = ($percentile / 100) * (count($values) - 1);
            $lowerIndex = floor($index);
            $upperIndex = ceil($index);

            if ($lowerIndex === $upperIndex) {
                $result[$percentile] = $values[$lowerIndex];
            } else {
                $weight = $index - $lowerIndex;
                $result[$percentile] = $values[$lowerIndex] * (1 - $weight) + $values[$upperIndex] * $weight;
            }
        }

        return $result;
    }

    /**
     * Calculate throughput (operations per second)
     */
    public static function calculateThroughput(int $operations, float $totalTime): int
    {
        return $totalTime > 0 ? (int)($operations / $totalTime) : 0;
    }

    /**
     * Calculate memory usage in MB
     */
    public static function getMemoryUsage(): float
    {
        return memory_get_usage(true) / 1024 / 1024;
    }

    /**
     * Calculate peak memory usage in MB
     */
    public static function getPeakMemoryUsage(): float
    {
        return memory_get_peak_usage(true) / 1024 / 1024;
    }

    /**
     * Calculate standard deviation
     */
    public static function calculateStandardDeviation(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }

        $mean = array_sum($values) / count($values);
        $variance = 0;

        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }

        return sqrt($variance / count($values));
    }

    /**
     * Calculate coefficient of variation (CV)
     */
    public static function calculateCoefficientOfVariation(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }

        $mean = array_sum($values) / count($values);
        if ($mean === 0) {
            return 0.0;
        }

        return self::calculateStandardDeviation($values) / $mean;
    }

    /**
     * Generate test data of specified size
     */
    public static function generateTestData(int $size): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $data = '';
        
        for ($i = 0; $i < $size; $i++) {
            $data .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $data;
    }

    /**
     * Generate mixed workload data (80% reads, 20% writes)
     */
    public static function generateMixedWorkload(int $totalOperations): array
    {
        $operations = [];
        $readCount = (int)($totalOperations * 0.8);
        $writeCount = $totalOperations - $readCount;

        // Add read operations
        for ($i = 0; $i < $readCount; $i++) {
            $operations[] = 'read';
        }

        // Add write operations
        for ($i = 0; $i < $writeCount; $i++) {
            $operations[] = 'write';
        }

        // Shuffle to randomize the order
        shuffle($operations);

        return $operations;
    }
}
