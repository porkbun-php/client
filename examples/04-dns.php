<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Porkbun\Exception\AuthenticationException;

$apiKey = getenv('PORKBUN_API_KEY') ?: '';
$secretKey = getenv('PORKBUN_SECRET_KEY') ?: '';
$domainName = getenv('PORKBUN_DOMAIN') ?: '';

if ($apiKey === '' || $secretKey === '' || $domainName === '') {
    echo "Set PORKBUN_API_KEY, PORKBUN_SECRET_KEY, and PORKBUN_DOMAIN environment variables.\n";
    exit(1);
}

$client = new Porkbun\Client();
$client->authenticate($apiKey, $secretKey);
$dns = $client->domain($domainName)->dns();

try {
    $records = $dns->all();
} catch (AuthenticationException) {
    echo "Authentication failed — check your API keys.\n";
    exit(1);
}

// List all records
echo "DNS records for {$domainName}:\n";
echo str_repeat('-', 60) . "\n";

foreach ($records as $record) {
    printf("%-20s %-6s %s\n", $record->name ?: '@', $record->dnsRecordType->value, $record->content);
}

echo "\nTotal: " . count($records) . " records\n";

// Collection helpers
$aRecords = $records->byType('A');
$rootRecords = $records->rootRecords;
$firstMx = $records->byType('MX')[0] ?? null;
echo "A records: " . count($aRecords) . ", root records: " . count($rootRecords) . "\n";
if ($firstMx !== null) {
    echo "First MX: {$firstMx->content} (priority {$firstMx->priority})\n";
}
if ($dns->isCloudflareEnabled) {
    echo "Cloudflare: enabled\n";
}

// Find by type from API (server-side filter)
$txtRecords = $dns->findByType('TXT');
echo "\nTXT records from API: " . count($txtRecords) . "\n";

// Find single record by ID
// $record = $dns->find(123456789);

// Create a record (commented out — creates real DNS record)
// $result = $dns->create('test', 'A', '192.0.2.1', ttl: 600, notes: 'Test record');
// echo "Created record ID: {$result->id}\n";

// Update by ID (commented out — modifies real record)
// $dns->update($recordId, 'test', 'A', '192.0.2.100', ttl: 1800);

// Update all matching records by type and name (commented out)
// $dns->updateByType('A', 'test', '192.0.2.200');

// Delete by ID (commented out)
// $dns->delete($recordId);

// Delete by type and name (commented out)
// $dns->deleteByType('A', 'test');
