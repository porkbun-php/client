<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Porkbun\Client;
use Porkbun\Exception\AuthenticationException;
use Porkbun\Exception\NetworkException;

$apiKey = getenv('PORKBUN_API_KEY') ?: '';
$secretKey = getenv('PORKBUN_SECRET_KEY') ?: '';

if ($apiKey === '' || $secretKey === '') {
    echo "Set PORKBUN_API_KEY and PORKBUN_SECRET_KEY environment variables first.\n";
    echo "Example: PORKBUN_API_KEY=pk1_xxx PORKBUN_SECRET_KEY=sk1_xxx php examples/01-ping.php\n";
    exit(1);
}

$client = Client::create($apiKey, $secretKey);

try {
    // Test API connectivity (default dual-stack endpoint)
    $ping = $client->ping();
    echo "Ping OK — your IP: " . ($ping->ip() ?? 'unknown') . "\n";

    // Switch to IPv4-only endpoint and test again
    $client->useIpv4Endpoint();
    $ping = $client->ping();
    echo "Ping OK (IPv4) — your IP: " . ($ping->ip() ?? 'unknown') . "\n";
} catch (AuthenticationException) {
    echo "Authentication failed — check your API keys.\n";
    exit(1);
} catch (NetworkException $e) {
    echo "Network error: " . $e->getMessage() . "\n";
    exit(1);
}
