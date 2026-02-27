<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Porkbun\Builder\DnsBatchBuilder;

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

// Mix builder and raw approaches in one batch
$batch = new DnsBatchBuilder();
$batch
    ->add($dns->record()->name('www')->a('192.0.2.1')->ttl(3600))
    ->add($dns->record()->name('mail')->mx('mail.example.com', 10))
    ->addRecord('api', 'A', '192.0.2.2', ttl: 600);

echo "Queued operations: {$batch->operationsCount()}\n";

// Edit and delete can also be batched
// $batch->updateRecord($existingId, ['content' => '192.0.2.100', 'ttl' => '1800']);
// $batch->deleteRecord($oldRecordId);
// $batch->deleteByNameType('A', 'deprecated-subdomain');

// Shared template with batch
$base = $dns->record()->ttl(3600)->notes('Load balancer');
$lbBatch = new DnsBatchBuilder();
$lbBatch
    ->add($base->name('lb1')->a('10.0.1.1'))
    ->add($base->name('lb2')->a('10.0.1.2'))
    ->add($base->name('lb3')->a('10.0.1.3'));

echo "Load balancer batch: {$lbBatch->operationsCount()} operations\n";

// Rollback clears all queued operations without executing
$lbBatch->rollback();
echo "After rollback: {$lbBatch->operationsCount()} operations\n";

// Execute and handle results (commented out — creates real records)
// $results = $batch->execute($dns);
// foreach ($results as $result) {
//     if ($result->success) {
//         echo "OK: {$result->operation}";
//         if ($result->hasRecordId) {
//             echo " (ID: {$result->recordId})";
//         }
//         echo "\n";
//     } else {
//         echo "FAIL: {$result->operation} — {$result->error}\n";
//     }
// }
