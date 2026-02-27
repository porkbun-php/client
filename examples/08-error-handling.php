<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Porkbun\Exception\ApiException;
use Porkbun\Exception\AuthenticationException;
use Porkbun\Exception\ExceptionInterface;
use Porkbun\Exception\NetworkException;

$client = new Porkbun\Client();
$client->authenticate('pk1_invalid', 'sk1_invalid');

// Full exception hierarchy — catch from most specific to least
try {
    $client->domains()->all();
} catch (AuthenticationException $e) {
    echo "Auth error (HTTP {$e->getStatusCode()}): {$e->getMessage()}\n";
    // AuthenticationException has getRequest() and getResponse()
} catch (ApiException $e) {
    echo "API error (HTTP {$e->getStatusCode()}): {$e->getMessage()}\n";
    if ($e->hasResponse()) {
        echo "Response body available for inspection\n";
    }
} catch (NetworkException $e) {
    echo "Network error: {$e->getMessage()}\n";
    if ($e->hasRequest()) {
        echo "Failed request: {$e->getRequest()->getUri()}\n";
    }
} catch (ExceptionInterface) {
    // Catches all library exceptions (including InvalidArgumentException)
    echo "Porkbun library error\n";
}

// Endpoint fallback pattern — only useful with valid credentials
$apiKey = getenv('PORKBUN_API_KEY') ?: '';
$secretKey = getenv('PORKBUN_SECRET_KEY') ?: '';

if ($apiKey !== '' && $secretKey !== '') {
    $client = new Porkbun\Client();
    $client->authenticate($apiKey, $secretKey);

    try {
        $client->useDefaultEndpoint();
        $ping = $client->ping();
        echo "\nPing OK (default): {$ping->resolvedIp}\n";
    } catch (NetworkException) {
        echo "\nDefault endpoint failed, trying IPv4...\n";

        try {
            $client->useIpv4Endpoint();
            $ping = $client->ping();
            echo "Ping OK (IPv4): {$ping->resolvedIp}\n";
        } catch (NetworkException $e) {
            echo "All endpoints failed: {$e->getMessage()}\n";
        }
    }
} else {
    echo "\nSkipping fallback demo — set PORKBUN_API_KEY and PORKBUN_SECRET_KEY to test.\n";
}
