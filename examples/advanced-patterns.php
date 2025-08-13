<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Porkbun\Client;
use Porkbun\Middleware\LoggingMiddleware;
use Porkbun\Middleware\RateLimitMiddleware;
use Porkbun\Request\CreateDnsRecordRequest;
use Psr\Log\NullLogger;

// Initialize client
$client = new Client();
$client->setAuth('pk1_your_api_key', 'sk1_your_secret_key');

echo "=== Advanced Patterns Demo ===\n\n";

// 1. Builder Pattern for DNS Records
echo "1. Builder Pattern - Creating DNS records with fluent interface:\n";

$dns = $client->dns('example.com');

// Create records using builder pattern
$recordId1 = $dns->createFromBuilder(
    $dns->record()
        ->name('www')
        ->a('192.168.1.100')
        ->ttl(3600)
        ->notes('Web server - created with builder')
);

$recordId2 = $dns->createFromBuilder(
    $dns->record()
        ->name('mail')
        ->mx('mail.example.com', 10)
        ->ttl(7200)
        ->notes('Mail server - priority 10')
);

$recordId3 = $dns->createFromBuilder(
    $dns->record()
        ->name('_dmarc')
        ->txt('v=DMARC1; p=reject; rua=mailto:dmarc@example.com')
        ->notes('DMARC policy')
);

echo "Created DNS records with IDs: $recordId1, $recordId2, $recordId3\n\n";

// 2. Batch Operations
echo "2. Batch Operations - Multiple DNS operations in one transaction:\n";

$results = $dns->batch()
    ->addRecord('api', 'A', '10.0.0.1', 3600, 0, 'API server')
    ->addRecord('cdn', 'CNAME', 'cdn.provider.com', 3600)
    ->addRecord('backup', 'A', '192.168.1.200', 7200, 0, 'Backup server')
    ->editRecord($recordId1, ['ttl' => '1800']) // Update TTL
    ->commit();

echo "Batch operations completed:\n";
foreach ($results as $i => $result) {
    echo "  Operation " . ($i + 1) . ": " . $result['status'] .
         " (" . $result['operation'] . ")\n";
}
echo "\n";

// 3. Request/Response Objects with Type Safety
echo "3. Request/Response Objects - Type-safe API interactions:\n";

// Using typed request objects
$createRequest = new CreateDnsRecordRequest(
    domain: 'example.com',
    name: 'secure',
    type: 'A',
    content: '10.10.10.10',
    ttl: 300,
    priority: 0,
    notes: 'High-security server with short TTL'
);

$createResponse = $dns->createFromRequest($createRequest);

if ($createResponse->isSuccess()) {
    echo "Created secure server record with ID: " . $createResponse->getId() . "\n";
}

// Using typed response objects for retrieval
$recordsResponse = $dns->retrieveAsResponse();

echo "Found " . $recordsResponse->getRecordCount() . " DNS records:\n";
foreach ($recordsResponse->getRecords() as $record) {
    echo "  - {$record['name']} ({$record['type']}) -> {$record['content']}\n";
}

// Filter records by type
$aRecords = $recordsResponse->getRecordsByType('A');
echo "\nA records only (" . count($aRecords) . " found):\n";
foreach ($aRecords as $record) {
    echo "  - {$record['name']} -> {$record['content']}\n";
}
echo "\n";

// 4. Middleware Support
echo "4. Middleware Support - Cross-cutting concerns:\n";

// Add logging middleware
$logger = new NullLogger(); // Use your preferred logger implementation
$loggingMiddleware = new LoggingMiddleware($logger);

// Add rate limiting middleware (10 requests per minute)
$rateLimitMiddleware = new RateLimitMiddleware(maxRequests: 10, windowSeconds: 60);

$pricing = $client->pricing();
$pricing->addMiddleware($loggingMiddleware);
$pricing->addMiddleware($rateLimitMiddleware);

echo "Middleware stack configured (logging + rate limiting)\n";
echo "Remaining requests in rate limit window: " .
     $rateLimitMiddleware->getRemainingRequests() . "\n";

// Use typed response for pricing
$pricingResponse = $pricing->getPricingAsResponse();

if ($pricingResponse->isSuccess()) {
    echo "Pricing data loaded for " . count($pricingResponse->getAllTlds()) . " TLDs\n";

    // Get specific domain prices
    if ($pricingResponse->hasDomain('com')) {
        echo ".com registration: $" . $pricingResponse->getRegistrationPrice('com') . "\n";
        echo ".com renewal: $" . $pricingResponse->getRenewalPrice('com') . "\n";
    }
}

echo "Remaining requests after pricing call: " .
     $rateLimitMiddleware->getRemainingRequests() . "\n\n";

// 5. Advanced Builder Usage
echo "5. Advanced Builder Usage - Complex record configurations:\n";

// Reset and reuse builder
$builder = $dns->record();

// Create multiple A records for load balancing
$loadBalancerIPs = ['192.168.1.10', '192.168.1.11', '192.168.1.12'];
$lbRecordIds = [];

foreach ($loadBalancerIPs as $i => $ip) {
    $recordId = $dns->createFromBuilder(
        $builder->reset()
            ->name('lb' . ($i + 1))
            ->a($ip)
            ->ttl(300) // Short TTL for load balancing
            ->notes("Load balancer server " . ($i + 1))
    );
    $lbRecordIds[] = $recordId;
}

echo "Created load balancer records with IDs: " . implode(', ', $lbRecordIds) . "\n";

// Batch delete old records
$cleanupResults = $dns->batch()
    ->deleteRecord($recordId1) // Remove old www record
    ->deleteByNameType('TXT', '_old') // Clean up old TXT records
    ->commit();

echo "Cleanup operations: " . count($cleanupResults) . " completed\n\n";

// 6. Error Handling with New Patterns
echo "6. Error Handling - Graceful degradation:\n";

try {
    $invalidBuilder = $dns->record();
    // This will throw InvalidArgumentException
    $invalidBuilder->type('INVALID_TYPE')->content('test');
} catch (\Porkbun\Exception\InvalidArgumentException $e) {
    echo "Builder validation caught: " . $e->getMessage() . "\n";
}

// Batch operations with error handling
$mixedResults = $dns->batch()
    ->addRecord('valid', 'A', '1.1.1.1') // This should succeed
    ->deleteRecord(999999) // This will likely fail
    ->addRecord('another', 'A', '2.2.2.2') // This should succeed
    ->commit();

echo "Mixed batch results:\n";
foreach ($mixedResults as $i => $result) {
    $status = $result['status'] === 'success' ? '✓' : '✗';
    echo "  $status Operation " . ($i + 1) . ": " . $result['status'] . "\n";
}

echo "\n=== Advanced Patterns Demo Complete ===\n";
