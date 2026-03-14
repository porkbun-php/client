<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Start without auth — public endpoints still work
$client = new Porkbun\Client();
$pricing = $client->pricing()->all();
echo "TLDs available: " . count($pricing) . "\n";

// Add credentials for account 1
$client->authenticate(
    getenv('PORKBUN_API_KEY_1') ?: 'pk1_account1',
    getenv('PORKBUN_SECRET_KEY_1') ?: 'sk1_account1'
);
echo "\nAccount 1 authenticated: " . ($client->isAuthenticated() ? 'yes' : 'no') . "\n";
// $domains1 = $client->domains()->list();

// Switch to account 2
$client->authenticate(
    getenv('PORKBUN_API_KEY_2') ?: 'pk1_account2',
    getenv('PORKBUN_SECRET_KEY_2') ?: 'sk1_account2'
);
echo "Switched to account 2\n";
// $domains2 = $client->domains()->list();

// Back to public-only mode
$client->clearAuth();
echo "Auth cleared: " . ($client->isAuthenticated() ? 'yes' : 'no') . "\n";
// $client->pricing()->all(); // still works
