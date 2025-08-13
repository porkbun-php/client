<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Porkbun\Client;

// Start with no configuration
$client = new Client();

try {
    // Get pricing (no auth required)
    $pricing = $client->pricing()->getPricing();
    echo "COM registration price: $" . $pricing['pricing']['com']['registration'] . "\n";

    // Add authentication for protected endpoints
    $client->setAuth('your-api-key', 'your-secret-api-key');

    // Test authentication
    $ping = $client->auth()->ping();
    echo "Your IP: " . $ping['yourIp'] . "\n";

    // List domains
    $domains = $client->domains()->listAll();
    echo "You have " . count($domains['domains']) . " domains\n";

    // Work with DNS for a specific domain
    $dns = $client->dns('example.com');
    $records = $dns->retrieve();
    echo "Domain has " . count($records['records']) . " DNS records\n";

} catch (\Porkbun\Exception\AuthenticationException $e) {
    echo "Authentication error: " . $e->getMessage() . "\n";
} catch (\Porkbun\Exception\ApiException $e) {
    echo "API error: " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
