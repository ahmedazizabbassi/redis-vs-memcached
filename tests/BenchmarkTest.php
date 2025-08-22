<?php

namespace CacheBenchmark\Tests;

use PHPUnit\Framework\TestCase;
use CacheBenchmark\Config\Configuration;
use CacheBenchmark\Utils\Statistics;
use CacheBenchmark\Models\BenchmarkResult;

class BenchmarkTest extends TestCase
{
    public function testStatisticsPercentiles()
    {
        $values = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $percentiles = Statistics::calculatePercentiles($values);
        
        $this->assertEquals(5.5, $percentiles[50], 'P50 should be 5.5');
        $this->assertEquals(9.5, $percentiles[95], 'P95 should be 9.5');
        $this->assertEquals(9.9, $percentiles[99], 'P99 should be 9.9');
    }

    public function testStatisticsThroughput()
    {
        $throughput = Statistics::calculateThroughput(1000, 2.0);
        $this->assertEquals(500, $throughput, 'Throughput should be 500 ops/sec');
    }

    public function testStatisticsTestDataGeneration()
    {
        $data = Statistics::generateTestData(100);
        $this->assertEquals(100, strlen($data), 'Generated data should be 100 characters');
        
        $data2 = Statistics::generateTestData(100);
        $this->assertNotEquals($data, $data2, 'Generated data should be random');
    }

    public function testStatisticsMixedWorkload()
    {
        $workload = Statistics::generateMixedWorkload(100);
        $this->assertEquals(100, count($workload), 'Workload should have 100 operations');
        
        $reads = array_count_values($workload)['read'] ?? 0;
        $writes = array_count_values($workload)['write'] ?? 0;
        
        $this->assertEquals(80, $reads, 'Should have 80% reads');
        $this->assertEquals(20, $writes, 'Should have 20% writes');
    }

    public function testBenchmarkResultCreation()
    {
        $result = new BenchmarkResult(
            operation: 'TEST_OP',
            cacheType: 'Redis',
            iterations: 1000,
            totalTime: 1000.0,
            averageTime: 1.0,
            minTime: 0.5,
            maxTime: 5.0,
            p50Time: 1.0,
            p95Time: 2.0,
            p99Time: 3.0,
            throughput: 1000,
            memoryUsage: 10.5,
            errors: 0
        );
        
        $this->assertEquals('TEST_OP', $result->operation);
        $this->assertEquals('Redis', $result->cacheType);
        $this->assertEquals(1000, $result->iterations);
        $this->assertEquals(1000.0, $result->totalTime);
        $this->assertEquals(1.0, $result->averageTime);
        $this->assertEquals(1000, $result->throughput);
    }

    public function testBenchmarkResultSerialization()
    {
        $result = new BenchmarkResult(
            operation: 'TEST_OP',
            cacheType: 'Memcached',
            iterations: 500,
            totalTime: 500.0,
            averageTime: 1.0,
            minTime: 0.5,
            maxTime: 3.0,
            p50Time: 1.0,
            p95Time: 2.0,
            p99Time: 2.5,
            throughput: 1000,
            memoryUsage: 5.0,
            errors: 0
        );
        
        $array = $result->toArray();
        $this->assertIsArray($array);
        $this->assertEquals('TEST_OP', $array['operation']);
        $this->assertEquals('Memcached', $array['cache_type']);
        
        $json = $result->toJson();
        $this->assertIsString($json);
        $this->assertStringContainsString('TEST_OP', $json);
    }

    public function testConfigurationLoading()
    {
        // Test with default values when no .env file exists
        $config = new Configuration('nonexistent.env');
        
        $redisConfig = $config->getRedisConfig();
        $this->assertEquals('127.0.0.1', $redisConfig['host']);
        $this->assertEquals(6379, $redisConfig['port']);
        
        $memcachedConfig = $config->getMemcachedConfig();
        $this->assertEquals('127.0.0.1', $memcachedConfig['host']);
        $this->assertEquals(11211, $memcachedConfig['port']);
        
        $benchmarkConfig = $config->getBenchmarkConfig();
        $this->assertEquals(1000, $benchmarkConfig['iterations']);
        $this->assertEquals(10, $benchmarkConfig['concurrent_connections']);
    }

    public function testMemoryUsageCalculation()
    {
        $memory = Statistics::getMemoryUsage();
        $this->assertIsFloat($memory);
        $this->assertGreaterThan(0, $memory);
        
        $peakMemory = Statistics::getPeakMemoryUsage();
        $this->assertIsFloat($peakMemory);
        $this->assertGreaterThanOrEqual($memory, $peakMemory);
    }

    public function testStandardDeviationCalculation()
    {
        $values = [1, 2, 3, 4, 5];
        $stdDev = Statistics::calculateStandardDeviation($values);
        $this->assertIsFloat($stdDev);
        $this->assertGreaterThan(0, $stdDev);
        
        // Test with empty array
        $stdDevEmpty = Statistics::calculateStandardDeviation([]);
        $this->assertEquals(0.0, $stdDevEmpty);
    }

    public function testCoefficientOfVariation()
    {
        $values = [1, 2, 3, 4, 5];
        $cv = Statistics::calculateCoefficientOfVariation($values);
        $this->assertIsFloat($cv);
        $this->assertGreaterThan(0, $cv);
        
        // Test with zero mean
        $cvZero = Statistics::calculateCoefficientOfVariation([0, 0, 0]);
        $this->assertEquals(0.0, $cvZero);
    }
}
