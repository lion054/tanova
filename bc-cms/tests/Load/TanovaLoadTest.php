<?php

namespace Tests\Load;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Response\StreamableInterface;

/**
 * Load Test for Tanova Trip Generation
 * Run: php artisan tinker < tests/Load/TanovaLoadTest.php
 * Or: php tests/load-test.php
 */
class TanovaLoadTest
{
    protected $baseUrl = 'http://127.0.0.1:8000/api/v';
    protected $apiKey = 'sk_live_test_key_replace_me';
    protected $client;
    protected $results = [];

    public function __construct()
    {
        $this->client = HttpClient::create([
            'timeout' => 30,
        ]);
    }

    /**
     * Test concurrent requests (10, 25, 50 concurrent users)
     */
    public function testConcurrentTripsGeneration()
    {
        echo "Testing concurrent trip generation...\n";

        $concurrencies = [10, 25, 50];

        foreach ($concurrencies as $concurrency) {
            echo "\n=== Testing with $concurrency concurrent requests ===\n";

            $startTime = microtime(true);
            $successCount = 0;
            $errorCount = 0;
            $times = [];

            $promises = [];

            for ($i = 0; $i < $concurrency; $i++) {
                $requestStart = microtime(true);

                $promise = $this->client->requestAsync('POST', "$this->baseUrl/tanova/generate", [
                    'headers' => [
                        'Authorization' => "Bearer {$this->apiKey}",
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'destination' => 'Victoria Falls',
                        'start_date' => '2026-06-15',
                        'end_date' => '2026-06-20',
                        'guests' => mt_rand(1, 8),
                        'budget' => ['budget', 'mid-range', 'luxury'][mt_rand(0, 2)],
                    ],
                ]);

                $promises[] = [
                    'promise' => $promise,
                    'start' => $requestStart,
                ];
            }

            // Process responses
            foreach ($promises as $item) {
                try {
                    $response = $item['promise']->getResponse();
                    $elapsed = (microtime(true) - $item['start']) * 1000;
                    $times[] = $elapsed;

                    if ($response->getStatusCode() === 201) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        echo "Error: {$response->getStatusCode()}\n";
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    echo "Exception: {$e->getMessage()}\n";
                }
            }

            $totalTime = (microtime(true) - $startTime) * 1000;

            echo "Results:\n";
            echo "  Success: $successCount / $concurrency\n";
            echo "  Errors: $errorCount\n";
            echo "  Total time: " . round($totalTime, 2) . "ms\n";
            echo "  Average time: " . round(array_sum($times) / count($times), 2) . "ms\n";
            echo "  Min time: " . round(min($times), 2) . "ms\n";
            echo "  Max time: " . round(max($times), 2) . "ms\n";
            echo "  P95 time: " . round($this->percentile($times, 95), 2) . "ms\n";

            // Store results
            $this->results[$concurrency] = [
                'success' => $successCount,
                'errors' => $errorCount,
                'total_ms' => $totalTime,
                'avg_ms' => array_sum($times) / count($times),
                'p95_ms' => $this->percentile($times, 95),
            ];

            sleep(2); // Wait between test runs
        }

        $this->reportResults();
    }

    /**
     * Test sustained load (maintain constant rate for 5 minutes)
     */
    public function testSustainedLoad()
    {
        echo "\n\nTesting sustained load (5 requests/sec for 5 minutes)...\n";

        $requestsPerSec = 5;
        $durationSeconds = 300; // 5 minutes
        $totalRequests = $requestsPerSec * $durationSeconds;

        $startTime = time();
        $successCount = 0;
        $errorCount = 0;
        $times = [];

        for ($i = 0; $i < $totalRequests; $i++) {
            $requestStart = microtime(true);

            try {
                $response = $this->client->request('POST', "$this->baseUrl/tanova/generate", [
                    'headers' => [
                        'Authorization' => "Bearer {$this->apiKey}",
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'destination' => 'Victoria Falls',
                        'start_date' => '2026-06-15',
                        'end_date' => '2026-06-20',
                        'guests' => mt_rand(1, 8),
                        'budget' => ['budget', 'mid-range', 'luxury'][mt_rand(0, 2)],
                    ],
                ]);

                $elapsed = (microtime(true) - $requestStart) * 1000;
                $times[] = $elapsed;

                if ($response->getStatusCode() === 201) {
                    $successCount++;
                } else {
                    $errorCount++;
                }

                // Throttle to requested rate
                $elapsed = time() - $startTime;
                $expectedRequests = $elapsed * $requestsPerSec;
                if ($i < $expectedRequests - 1) {
                    usleep(10000); // Sleep 10ms
                }
            } catch (\Exception $e) {
                $errorCount++;
                echo "Error: {$e->getMessage()}\n";
            }

            if (($i + 1) % 50 === 0) {
                echo ".";
            }
        }

        echo "\n\nSustained load results:\n";
        echo "  Total requests: $totalRequests\n";
        echo "  Success: $successCount\n";
        echo "  Errors: $errorCount\n";
        echo "  Success rate: " . round($successCount / $totalRequests * 100, 2) . "%\n";
        echo "  Average response: " . round(array_sum($times) / count($times), 2) . "ms\n";
        echo "  P95 response: " . round($this->percentile($times, 95), 2) . "ms\n";
        echo "  P99 response: " . round($this->percentile($times, 99), 2) . "ms\n";
    }

    protected function percentile($data, $percentile)
    {
        sort($data);
        $index = ceil((count($data) * $percentile) / 100) - 1;
        return $data[$index] ?? end($data);
    }

    protected function reportResults()
    {
        echo "\n\n=== LOAD TEST SUMMARY ===\n";
        echo "Production ready if:\n";
        echo "  ✓ All concurrency levels have >95% success rate\n";
        echo "  ✓ P95 response time <1000ms\n";
        echo "  ✓ No errors under sustained load\n";
        echo "  ✓ Error rate <5%\n";

        foreach ($this->results as $concurrency => $result) {
            $successRate = ($result['success'] / ($result['success'] + $result['errors'])) * 100;
            $status = $successRate >= 95 && $result['p95_ms'] < 1000 ? '✓ PASS' : '✗ FAIL';
            echo "\n$status - Concurrency $concurrency: {$successRate}% success, P95: {$result['p95_ms']}ms\n";
        }
    }
}

// Run tests
$test = new TanovaLoadTest();
$test->testConcurrentTripsGeneration();
$test->testSustainedLoad();
