<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Porkbun\Client;
use Porkbun\Exception\ApiException;
use Porkbun\Exception\AuthenticationException;
use Porkbun\Exception\NetworkException;

$apiKey = getenv('PORKBUN_API_KEY') ?: '';
$secretKey = getenv('PORKBUN_SECRET_KEY') ?: '';

if ($apiKey === '' || $secretKey === '') {
    echo "Set PORKBUN_API_KEY and PORKBUN_SECRET_KEY environment variables first.\n";
    echo "Example: PORKBUN_API_KEY=pk1_xxx PORKBUN_SECRET_KEY=sk1_xxx php examples/03-domains.php\n";
    exit(1);
}

$client = Client::create($apiKey, $secretKey);

// ============================================================
// List all domains in account
// ============================================================

echo "Your domains:\n";
echo str_repeat('-', 60) . "\n";

try {
    $domainList = $client->domains()->all();
} catch (AuthenticationException) {
    echo "Authentication failed — check your API keys.\n";
    exit(1);
} catch (NetworkException $e) {
    echo "Network error: " . $e->getMessage() . "\n";
    exit(1);
}

foreach ($domainList as $domainInfo) {
    $expireDate = $domainInfo->expireDate?->format('Y-m-d') ?? 'unknown';
    $autoRenew = $domainInfo->autoRenew ? 'on' : 'off';
    printf(
        "%-30s expires: %s  auto-renew: %s\n",
        $domainInfo->domain,
        $expireDate,
        $autoRenew
    );
}

echo "\nTotal: " . count($domainList) . " domains\n";

// Domains expiring within 30 days
$expiringSoon = $domainList->getExpiringSoon(30);
if ($expiringSoon !== []) {
    echo "\nExpiring within 30 days:\n";
    foreach ($expiringSoon as $domain) {
        echo "  {$domain->domain} — {$domain->expireDate?->format('Y-m-d')}\n";
    }
}

// ============================================================
// Check domain availability (uses the domain facade)
// ============================================================

$domainToCheck = 'example-domain-check.com';

echo "\nChecking availability of {$domainToCheck}...\n";

try {
    $availability = $client->domain($domainToCheck)->check();
} catch (ApiException $e) {
    echo "API error: " . $e->getMessage() . "\n";
    exit(1);
}

if ($availability->isAvailable) {
    echo "Domain is AVAILABLE\n";
    $price = $availability->getEffectivePrice();
    if ($price !== null) {
        printf("  Registration: \$%.2f", $price);
        if ($availability->hasPromoPrice()) {
            printf(" (regular: \$%.2f, save \$%.2f)", $availability->regularPrice, $availability->getPromoSavings());
        }
        echo "\n";
    }
    if ($availability->renewalPrice !== null) {
        printf("  Renewal: \$%.2f/year\n", $availability->renewalPrice);
    }
    if ($availability->isPremium) {
        echo "  Note: this is a premium domain\n";
    }
} else {
    echo "Domain is NOT available\n";
}

// ============================================================
// Domain-specific operations using the Domain facade
// ============================================================

if ($domainList->isEmpty()) {
    echo "\nNo domains in account, skipping domain-specific examples.\n";
    exit(0);
}

$firstDomain = $domainList->first();
$testDomainName = $firstDomain->domain;
$domain = $client->domain($testDomainName);

// ============================================================
// Nameservers
// ============================================================

echo "\nNameservers for {$testDomainName}:\n";

try {
    $nameservers = $domain->nameservers()->all();
    foreach ($nameservers as $ns) {
        echo "  - {$ns}\n";
    }
} catch (ApiException $e) {
    echo "  Error: {$e->getMessage()}\n";
}

// Update nameservers (commented out — destructive operation)
// $domain->nameservers()->update(['ns1.example.com', 'ns2.example.com']);

// ============================================================
// URL Forwarding
// ============================================================

echo "\nURL forwards for {$testDomainName}:\n";

try {
    $forwards = $domain->urlForwarding()->all();
    if (count($forwards) === 0) {
        echo "  (none)\n";
    } else {
        foreach ($forwards as $forward) {
            echo "  {$forward->subdomain}.{$testDomainName} -> {$forward->location}\n";
        }
    }
} catch (ApiException $e) {
    echo "  Error: {$e->getMessage()}\n";
}

// Add URL forward (commented out — creates real record)
// $domain->urlForwarding()->add([
//     'subdomain' => 'go',
//     'location' => 'https://destination.example.com',
//     'type' => 'temporary',
//     'includePath' => 'no',
//     'wildcard' => 'no',
// ]);

// ============================================================
// Glue Records (for custom nameservers)
// ============================================================

echo "\nGlue records for {$testDomainName}:\n";

try {
    $glueRecords = $domain->glue()->all();
    if (count($glueRecords) === 0) {
        echo "  (none)\n";
    } else {
        foreach ($glueRecords as $glue) {
            $ips = implode(', ', $glue->ips);
            echo "  {$glue->host} -> {$ips}\n";
        }
    }
} catch (ApiException $e) {
    echo "  Error: {$e->getMessage()}\n";
}

// ============================================================
// SSL Certificate
// ============================================================

echo "\nSSL certificate for {$testDomainName}:\n";

try {
    $ssl = $domain->ssl()->get();
    if ($ssl->hasCertificate()) {
        echo "  Certificate available\n";
    } else {
        echo "  No certificate\n";
    }
} catch (ApiException $e) {
    echo "  Not available: {$e->getMessage()}\n";
}

echo "\nDone!\n";
