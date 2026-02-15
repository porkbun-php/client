<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Porkbun\Client;
use Porkbun\Exception\NetworkException;

// Pricing API is public — no authentication required
$client = Client::create();

try {
    $pricing = $client->pricing()->all();
} catch (NetworkException $e) {
    echo "Network error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Total TLDs available: " . count($pricing) . "\n\n";

// Pricing for popular TLDs
$tlds = ['com', 'net', 'org', 'dev', 'io', 'ru'];

echo "Popular TLD Pricing:\n";
echo str_repeat('-', 50) . "\n";

foreach ($tlds as $tld) {
    $item = $pricing->get($tld);
    if ($item !== null) {
        printf(
            ".%-6s Register: $%7.2f  Renew: $%7.2f\n",
            $item->tld,
            $item->registrationPrice,
            $item->renewalPrice
        );
    }
}

// Quick price lookup via get()
echo "\nQuick lookup:\n";
$com = $pricing->get('com');
echo ".com registration: " . ($com !== null ? sprintf('$%.2f', $com->registrationPrice) : 'N/A') . "\n";
echo ".com renewal: " . ($com !== null ? sprintf('$%.2f', $com->renewalPrice) : 'N/A') . "\n";

// 5 cheapest TLDs
echo "\nTop 5 Cheapest TLDs:\n";
echo str_repeat('-', 50) . "\n";

foreach ($pricing->cheapest(5) as $item) {
    printf(
        ".%-10s $%.2f/year\n",
        $item->tld,
        $item->registrationPrice
    );
}

// All TLDs under $5/year
echo "\nAll TLDs under \$5/year:\n";

foreach ($pricing as $tld => $item) {
    if ($item->registrationPrice < 5.00) {
        echo ".{$tld} ";
    }
}
echo "\n";
