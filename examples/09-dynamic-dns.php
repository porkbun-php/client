<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$apiKey = getenv('PORKBUN_API_KEY') ?: '';
$secretKey = getenv('PORKBUN_SECRET_KEY') ?: '';
$domainName = getenv('PORKBUN_DOMAIN') ?: '';
$subdomain = getenv('PORKBUN_SUBDOMAIN') ?: 'home';

if ($apiKey === '' || $secretKey === '' || $domainName === '') {
    echo "Set PORKBUN_API_KEY, PORKBUN_SECRET_KEY, and PORKBUN_DOMAIN environment variables.\n";
    echo "Optionally set PORKBUN_SUBDOMAIN (default: home).\n";
    exit(1);
}

$client = new Porkbun\Client();
$client->authenticate($apiKey, $secretKey);

// Use IPv4-only endpoint to get a deterministic IPv4 address
$client->useIpv4Endpoint();
$currentIp = $client->ping()->resolvedIp;
echo "Current IPv4: {$currentIp}\n";

$dns = $client->domain($domainName)->dns();
$existing = $dns->findByType('A', $subdomain);

if ($existing->isNotEmpty()) {
    $dns->updateByType('A', $subdomain, $currentIp);
    echo "Updated {$subdomain}.{$domainName} -> {$currentIp}\n";
} else {
    $dns->create($subdomain, 'A', $currentIp, ttl: 600);
    echo "Created {$subdomain}.{$domainName} -> {$currentIp}\n";
}
