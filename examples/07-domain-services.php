<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Porkbun\Exception\ApiException;
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
$domain = $client->domain($domainName);

// Domain details (metadata from account)
try {
    $details = $domain->details();
    echo "Domain: {$details->domain}\n";
    echo "  Status:       {$details->status}\n";
    echo '  Auto-renew:   ' . ($details->autoRenew ? 'on' : 'off') . "\n";
    echo '  Security lock: ' . ($details->securityLock ? 'on' : 'off') . "\n";
    echo '  Expires:      ' . ($details->expireDate?->format('Y-m-d') ?? 'unknown') . "\n";
    echo str_repeat('-', 60) . "\n";
} catch (ApiException $e) {
    echo "Could not fetch domain details: {$e->getMessage()}\n";
}

// Nameservers
try {
    $nameservers = $domain->nameservers()->all();
    echo "Nameservers:\n";
    foreach ($nameservers as $ns) {
        echo "  {$ns}\n";
    }
} catch (AuthenticationException) {
    echo "Authentication failed — check your API keys.\n";
    exit(1);
}

// $domain->nameservers()->update(['ns1.custom.com', 'ns2.custom.com']);

// URL Forwarding
$forwards = $domain->urlForwarding()->all();
echo "\nURL forwards: " . count($forwards) . "\n";
foreach ($forwards as $fwd) {
    echo "  {$fwd->subdomain}.{$domainName} -> {$fwd->location}\n";
}

// $domain->urlForwarding()->create('https://destination.example.com', 'temporary', subdomain: 'go');
// $domain->urlForwarding()->delete($forwardId);

// Glue Records
$glueRecords = $domain->glue()->all();
echo "\nGlue records: " . count($glueRecords) . "\n";
foreach ($glueRecords as $glue) {
    echo "  {$glue->host} -> " . implode(', ', $glue->ips) . "\n";
}

// $domain->glue()->create('ns1', ['192.0.2.1', '192.0.2.2']);
// $domain->glue()->update('ns1', ['192.0.2.10']);
// $domain->glue()->delete('ns1');

// DNSSEC
$dnssecRecords = $domain->dnssec()->all();
echo "\nDNSSEC records: " . count($dnssecRecords) . "\n";
foreach ($dnssecRecords as $record) {
    echo "  Key Tag {$record->keyTag} (alg: {$record->algorithmName}, digest: {$record->digestTypeName})\n";
}

// $domain->dnssec()->create(keyTag: 12345, algorithm: 13, digestType: 2, digest: 'abc123...');
// $domain->dnssec()->delete(12345);

// Auto-Renew
// $domain->autoRenew()->enable();
// $domain->autoRenew()->disable();

// SSL Certificate
try {
    $ssl = $domain->ssl()->get();
    echo "\nSSL: " . ($ssl->hasCertificate ? 'certificate available' : 'no certificate') . "\n";
} catch (ApiException $e) {
    echo "\nSSL: not available ({$e->getMessage()})\n";
}

// Domain Registration (commented out — purchases a domain)
// $result = $client->domain('newdomain.com')->register(868);
// echo "Registered: {$result->domain}, order #{$result->orderId}\n";
