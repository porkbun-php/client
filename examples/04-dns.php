<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Porkbun\Client;
use Porkbun\Exception\ApiException;
use Porkbun\Exception\AuthenticationException;
use Porkbun\Exception\NetworkException;

$apiKey = getenv('PORKBUN_API_KEY') ?: '';
$secretKey = getenv('PORKBUN_SECRET_KEY') ?: '';
$domainName = getenv('PORKBUN_DOMAIN') ?: '';

if ($apiKey === '' || $secretKey === '' || $domainName === '') {
    echo "Set PORKBUN_API_KEY, PORKBUN_SECRET_KEY, and PORKBUN_DOMAIN environment variables first.\n";
    echo "Example: PORKBUN_API_KEY=pk1_xxx PORKBUN_SECRET_KEY=sk1_xxx PORKBUN_DOMAIN=example.com php examples/04-dns.php\n";
    exit(1);
}

$client = Client::create($apiKey, $secretKey);
$dns = $client->domain($domainName)->dns();

// Unique suffix for test records (allows running example multiple times)
$suffix = bin2hex(random_bytes(4));

// ============================================================
// Retrieve all DNS records
// ============================================================

echo "Current DNS records for {$domainName}:\n";
echo str_repeat('-', 60) . "\n";

try {
    $records = $dns->all();
} catch (AuthenticationException) {
    echo "Authentication failed — check your API keys.\n";
    exit(1);
} catch (NetworkException $e) {
    echo "Network error: " . $e->getMessage() . "\n";
    exit(1);
}

foreach ($records as $record) {
    $displayName = $record->name !== '' ? $record->name : '@';
    printf(
        "%-20s %-6s %s\n",
        $displayName,
        $record->dnsRecordType->value,
        $record->content
    );
}

echo "\nTotal records: " . count($records) . "\n";

if ($records->isCloudflareEnabled()) {
    echo "Cloudflare: enabled\n";
}

// ============================================================
// Create records using direct method
// ============================================================

echo "\nCreating A record (direct method)...\n";

try {
    $result = $dns->create(
        name: "test-{$suffix}",
        type: 'A',
        content: '192.0.2.1',
        ttl: 600,
        notes: 'Test record'
    );
    echo "Created record ID: {$result->id}\n";
    $testRecordId = $result->id;
} catch (ApiException $e) {
    echo "Failed to create record: " . $e->getMessage() . "\n";
    exit(1);
}

// ============================================================
// Create records using the builder (recommended)
// ============================================================

echo "\nCreating records using builder...\n";

// A record
$result = $dns->createFromBuilder(
    $dns->record()
        ->name("api-{$suffix}")
        ->a('192.0.2.2')
        ->ttl(3600)
        ->notes('API server')
);
echo "Created A record: {$result->id}\n";
$apiRecordId = $result->id;

// MX record with priority
$result = $dns->createFromBuilder(
    $dns->record()
        ->name("mail-{$suffix}")
        ->mx('mail.example.com', priority: 10)
);
echo "Created MX record: {$result->id}\n";
$mxRecordId = $result->id;

// TXT record
$result = $dns->createFromBuilder(
    $dns->record()
        ->name("txt-{$suffix}")
        ->txt('v=spf1 include:_spf.example.com ~all')
);
echo "Created TXT record: {$result->id}\n";
$txtRecordId = $result->id;

// CNAME record
$result = $dns->createFromBuilder(
    $dns->record()
        ->name("www-{$suffix}")
        ->cname('example.com')
        ->ttl(3600)
);
echo "Created CNAME record: {$result->id}\n";
$cnameRecordId = $result->id;

// ============================================================
// Filter and query records
// ============================================================

echo "\nFiltering records...\n";

// Refresh records after creation
$records = $dns->all();

// Get only A records (client-side filter)
$aRecords = $records->getRecordsByType('A');
echo "A records: " . count($aRecords) . "\n";

// Get root records only (client-side filter)
$rootRecords = $records->getRootRecords();
echo "Root records: " . count($rootRecords) . "\n";

// Retrieve by type from API (server-side filter)
$mxRecords = $dns->findByType('MX');
echo "MX records from API: " . count($mxRecords) . "\n";

// Find single record by ID
$found = $dns->find($testRecordId);
if ($found !== null) {
    echo "Found record: {$found->name} {$found->dnsRecordType->value} {$found->content}\n";
}

// ============================================================
// Update records
// ============================================================

echo "\nUpdating records...\n";

// Update by ID (type is required by Porkbun API)
$dns->edit($testRecordId, [
    'type' => 'A',
    'content' => '192.0.2.100',
    'ttl' => '1800',
]);
echo "Updated record {$testRecordId}\n";

// Update all records matching type and name
$dns->update('A', "api-{$suffix}", [
    'content' => '192.0.2.200',
]);
echo "Updated all A records for 'api-{$suffix}' subdomain\n";

// ============================================================
// Delete records
// ============================================================

echo "\nCleaning up...\n";

// Delete by ID
$dns->delete($testRecordId);
echo "Deleted record {$testRecordId}\n";

// Delete by type and name
$dns->deleteByType('A', "api-{$suffix}");
echo "Deleted A record for 'api-{$suffix}'\n";

// Delete remaining test records
$dns->delete($mxRecordId);
$dns->delete($txtRecordId);
$dns->delete($cnameRecordId);
echo "Deleted remaining test records\n";

echo "\nDone!\n";
