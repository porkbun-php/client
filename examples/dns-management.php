<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Porkbun\Client;

// Create client with authentication
$client = new Client();
$client->setAuth('your-api-key', 'your-secret-api-key');

$domain = 'example.com';
$dns = $client->dns($domain);

try {
    // Create various DNS records
    echo "Creating DNS records...\n";

    $aRecord = $dns->create('www', 'A', '192.168.1.1', 3600, 0, 'Web server');
    echo "Created A record with ID: $aRecord\n";

    $mxRecord = $dns->create('', 'MX', 'mail.example.com', 3600, 10, 'Mail server');
    echo "Created MX record with ID: $mxRecord\n";

    $txtRecord = $dns->create('_dmarc', 'TXT', 'v=DMARC1; p=none;', 3600, 0, 'DMARC policy');
    echo "Created TXT record with ID: $txtRecord\n";

    // Retrieve all records
    echo "\nRetrieving all DNS records...\n";
    $allRecords = $dns->retrieve();
    foreach ($allRecords['records'] as $record) {
        echo "- {$record['type']} {$record['name']}.{$domain} → {$record['content']}\n";
    }

    // Retrieve specific record
    echo "\nRetrieving specific A record...\n";
    $specificRecord = $dns->retrieve($aRecord);
    echo "Record: {$specificRecord['records'][0]['name']}.{$domain}\n";

    // Retrieve by name and type
    echo "\nRetrieving www A records...\n";
    $wwwRecords = $dns->retrieveByNameType('A', 'www');
    echo "Found " . count($wwwRecords['records']) . " www A records\n";

    // Update a record
    echo "\nUpdating A record...\n";
    $dns->edit($aRecord, [
        'content' => '192.168.1.2',
        'ttl' => '7200',
    ]);
    echo "Updated A record content\n";

    // Update by name/type
    echo "\nUpdating all www A records...\n";
    $dns->editByNameType('A', 'www', [
        'content' => '192.168.1.3',
    ]);
    echo "Updated all www A records\n";

    // Create DNSSEC record
    echo "\nCreating DNSSEC record...\n";
    $dns->createDnssecRecord([
        'keyTag' => '12345',
        'alg' => '13',
        'digestType' => '2',
        'digest' => 'abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890',
    ]);
    echo "Created DNSSEC record\n";

    // Get DNSSEC records
    echo "\nRetrieving DNSSEC records...\n";
    $dnssecRecords = $dns->getDnssecRecords();
    echo "Found " . count($dnssecRecords['records']) . " DNSSEC records\n";

    // Clean up - delete test records
    echo "\nCleaning up test records...\n";
    $dns->delete($aRecord);
    $dns->delete($mxRecord);
    $dns->delete($txtRecord);
    echo "Deleted test records\n";

} catch (\Porkbun\Exception\AuthenticationException $e) {
    echo "Authentication error: " . $e->getMessage() . "\n";
} catch (\Porkbun\Exception\ApiException $e) {
    echo "API error: " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
