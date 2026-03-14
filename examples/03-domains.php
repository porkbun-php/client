<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Porkbun\Exception\AuthenticationException;

$apiKey = getenv('PORKBUN_API_KEY') ?: '';
$secretKey = getenv('PORKBUN_SECRET_KEY') ?: '';

if ($apiKey === '' || $secretKey === '') {
    echo "Set PORKBUN_API_KEY and PORKBUN_SECRET_KEY environment variables first.\n";
    exit(1);
}

$client = new Porkbun\Client();
$client->authenticate($apiKey, $secretKey);

try {
    $domains = $client->domains()->list();
} catch (AuthenticationException) {
    echo "Authentication failed — check your API keys.\n";
    exit(1);
}

echo "Your domains:\n";
echo str_repeat('-', 60) . "\n";

foreach ($domains as $domain) {
    $expires = $domain->expireDate?->format('Y-m-d') ?? 'unknown';
    $renew = $domain->autoRenew ? 'on' : 'off';
    printf("%-30s expires: %s  auto-renew: %s\n", $domain->domain, $expires, $renew);
}

echo "\nTotal: " . count($domains) . " domains\n";

// Look up a single domain by name
$first = $domains->first();
if ($first !== null) {
    $found = $client->domains()->find($first->domain);
    if ($found !== null) {
        echo "\nLookup '{$found->domain}': status={$found->status}";
        echo ', auto-renew=' . ($found->autoRenew ? 'on' : 'off');
        echo ', expires=' . ($found->expireDate?->format('Y-m-d') ?? 'unknown') . "\n";
    }

    // Same info via the domain facade
    $details = $client->domain($first->domain)->details();
    echo "Via facade: {$details->domain} (security-lock=" . ($details->securityLock ? 'on' : 'off') . ")\n";
}

// Domains expiring within 30 days
$expiring = $domains->expiringSoon(30);
if ($expiring->isNotEmpty()) {
    echo "\nExpiring within 30 days:\n";
    foreach ($expiring as $domain) {
        echo "  {$domain->domain} — {$domain->expireDate?->format('Y-m-d')}\n";
    }
}

// Check domain availability
$availability = $client->domain('example-check-test.com')->check();
echo "\nexample-check-test.com: " . ($availability->isAvailable ? 'available' : 'taken');
if ($availability->isAvailable && $availability->effectivePrice !== null) {
    printf(" (\$%.2f)", $availability->effectivePrice);
}
echo "\n";

// Auto-renew via domain facade (commented out — modifies account settings)
// $client->domain('domain1.com')->autoRenew()->enable();
// $client->domain('domain2.com')->autoRenew()->disable();
