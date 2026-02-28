# Porkbun PHP API Client

[![CI](https://github.com/porkbun-php/client/actions/workflows/ci.yml/badge.svg)](https://github.com/porkbun-php/client/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/porkbun-php/client/v)](https://packagist.org/packages/porkbun-php/client)
[![License](https://poser.pugx.org/porkbun-php/client/license)](https://packagist.org/packages/porkbun-php/client)

A community-maintained PHP 8.4+ client for the [Porkbun API v3](https://porkbun.com/api/json/v3/documentation) with complete endpoint coverage, domain-centric design, and Laravel integration.

## Features

- **Complete API coverage** — all 27 Porkbun API v3 endpoints: DNS, DNSSEC, SSL, nameservers, URL forwarding, glue records, domains, pricing, and more
- **Domain-centric design** — fluent API: `$client->domain('example.com')->dns()->all()`
- **Typed everything** — immutable DTOs, backed enums, strict return types throughout
- **Fluent builders** — validated DNS record construction with convenience methods
- **Structured errors** — typed exception hierarchy with request/response context
- **Laravel integration** — service provider, facade, and config publishing out of the box
- **PSR-18 compatible** — works with any HTTP client (Guzzle, Symfony, curl, etc.)
- **Strict quality** — PHPStan level max, Pest tests, PHP 8.4+ with Rector

## Requirements

- PHP 8.4+
- A PSR-18 HTTP client (Guzzle, Symfony HttpClient, or any other)

## Installation

```bash
composer require porkbun-php/client
```

If you don't already have a PSR-18 HTTP client installed, add one:

```bash
composer require guzzlehttp/guzzle
# or
composer require symfony/http-client nyholm/psr7
```

> [!TIP]
> Most frameworks already ship with a PSR-18 client — Laravel includes Guzzle, Symfony includes its HttpClient. The package auto-discovers it, so `new Porkbun\Client()` just works.

## Quick Start

Generate your API key and secret on the [API Access](https://porkbun.com/account/api) page. To manage a specific domain via API, enable **API Access** for that domain in your Porkbun control panel.

```php
$client = new Porkbun\Client();
$client->authenticate('pk1_your_api_key', 'sk1_your_secret_key');

// Test connectivity
$ping = $client->ping();
echo "Your IP: {$ping->resolvedIp}";

// Domain pricing (no auth required)
$pricing = $client->pricing()->all();
echo "COM: $" . $pricing->find('com')?->registrationPrice;

// Domain-centric operations
$domain = $client->domain('example.com');

// List DNS records
foreach ($domain->dns()->all() as $record) {
    echo "{$record->name} {$record->type->value} {$record->content}\n";
}

// Get SSL certificate
$cert = $domain->ssl()->get();
echo $cert->certificateChain;
```

<details>
<summary>Advanced configuration</summary>

```php
// Custom PSR-18 HTTP client
$client = new Porkbun\Client($myPsr18Client);

// IPv4-only endpoint (useful for dynamic DNS)
$client->useIpv4Endpoint();
$client->useDefaultEndpoint(); // back to dual-stack

// Switch accounts at runtime
$client->authenticate($account2Key, $account2Secret);
$client->clearAuth(); // back to unauthenticated
```

</details>

## API Reference

> [!NOTE]
> All endpoints require authentication except **Pricing** — you can query TLD pricing without API keys.

### Pricing (No Auth Required)

```php
$pricing = $client->pricing()->all();

$pricing->find('com')?->registrationPrice;  // float
$pricing->find('com')?->renewalPrice;       // float
$pricing->cheapest(10);                     // Top 10 cheapest TLDs
$pricing->tlds();                           // All available TLD keys
$pricing->first();                          // First PricingItem
$pricing->last();                           // Last PricingItem
```

### Ping (Auth Test)

```php
$ping = $client->ping();

$ping->resolvedIp; // Your IP address (prefers X-Forwarded-For)
$ping->yourIp;     // Raw IP from API response
```

### Domains

```php
// List all domains in your account
$domains = $client->domains()->all();

// With pagination
$domains = $client->domains()->all(start: 100);

// Iterate all pages automatically
foreach ($client->domains()->allPages() as $domain) {
    echo "{$domain->domain} expires {$domain->expireDate?->format('Y-m-d')}\n";
}
```

### Domain Availability

```php
$result = $client->domain('example.com')->check();

$result->isAvailable;           // bool
$result->price;                 // float (registration price)
$result->type;                  // 'standard', 'premium', etc.
$result->priceInCents();        // int (e.g., 999 for $9.99)
$result->effectivePrice;        // float (promo price if available, else regular)
```

### Domain Registration

```php
$result = $client->domain('newdomain.com')->register(868);

$result->domain;                 // 'newdomain.com'
$result->orderId;                // int
$result->costInDollars();        // float
$result->balanceInDollars();     // float
```

### DNS Records

> [!TIP]
> Use the **builder** for validated record creation, or **direct methods** when you need to bypass client-side validation.

```php
$dns = $client->domain('example.com')->dns();

// Retrieve
$dns->all();                          // DnsRecordCollection
$dns->find($recordId);               // DnsRecord or null
$dns->findByType('A');                // DnsRecordCollection
$dns->findByType('A', 'www');         // By type and subdomain
$dns->all()->first();                 // First DnsRecord or null
$dns->all()->last();                  // Last DnsRecord or null

// Create (direct)
$result = $dns->create('www', 'A', '192.0.2.1', ttl: 3600);
echo "Created record: {$result->id}";

// Create (builder - recommended)
$result = $dns->createFromBuilder(
    $dns->record()
        ->name('api')
        ->a('192.0.2.2')
        ->ttl(3600)
        ->notes('API server')
);

// Builder convenience methods
$dns->record()->name('mail')->mx('mail.provider.com', priority: 10);
$dns->record()->name('_dmarc')->txt('v=DMARC1; p=reject');
$dns->record()->name('blog')->cname('blog.provider.com');
$dns->record()->name('app')->aaaa('2001:db8::1');

// Enum types are also accepted
use Porkbun\Enum\DnsRecordType;
$dns->findByType(DnsRecordType::A);
$dns->create('www', DnsRecordType::A, '192.0.2.1');

// Update
$dns->update($recordId, 'www', 'A', '192.0.2.3');
$dns->updateByType('A', '192.0.2.3', 'www');

// Delete
$dns->delete($recordId);
$dns->deleteByType('A', 'old-subdomain');

// Collection helpers
$records = $dns->all();
$records->byType('MX');
$records->byName('www');
$records->rootRecords;
$records->byType('A')->first();

```

### DNSSEC Records

```php
$dnssec = $client->domain('example.com')->dnssec();

$dnssec->all();              // DnssecRecordCollection
$dnssec->create(keyTag: 12345, algorithm: 13, digestType: 2, digest: 'abc123...');
$dnssec->delete($keyTag);
```

### Batch DNS Operations

```php
use Porkbun\Builder\DnsBatchBuilder;

$dns = $client->domain('example.com')->dns();
$batch = new DnsBatchBuilder();

$results = $batch
    ->addRecord('www', 'A', '192.0.2.1')
    ->addRecord('api', 'A', '192.0.2.2')
    ->updateRecord($existingId, 'www', 'A', '192.0.2.3', ttl: 3600)
    ->deleteRecord($oldRecordId)
    ->execute($dns);

foreach ($results as $result) {
    if ($result->success) {
        echo "OK: {$result->operation}\n";
    } else {
        echo "Failed: {$result->error}\n";
    }
}
```

### SSL Certificates

```php
$cert = $client->domain('example.com')->ssl()->get();

$cert->certificateChain;
$cert->privateKey;
$cert->publicKey;
$cert->fullChain;                   // Chain + intermediate
$cert->hasPrivateKey;               // bool
$cert->hasCertificate;              // bool
$cert->hasIntermediateCertificate;  // bool
```

### Nameservers

```php
$ns = $client->domain('example.com')->nameservers();

$ns->all();              // NameserverCollection: ['ns1.porkbun.com', 'ns2.porkbun.com']
$ns->all()->first();    // 'ns1.porkbun.com' or null
$ns->all()->last();     // 'ns2.porkbun.com' or null
$ns->update(['ns1.custom.com', 'ns2.custom.com']);
```

### URL Forwarding

```php
$forwards = $client->domain('example.com')->urlForwarding();

$forwards->all();              // UrlForwardCollection
$forwards->all()->first();    // UrlForward or null
$forwards->create('https://destination.example.com', 'temporary', subdomain: 'go');
$forwards->delete($recordId);
```

### Glue Records

```php
$glue = $client->domain('example.com')->glue();

$glue->all();              // GlueRecordCollection
$glue->all()->first();    // GlueRecord or null
$glue->create('ns1', ['192.0.2.1', '192.0.2.2']);
$glue->update('ns1', ['192.0.2.10']);
$glue->delete('ns1');
```

### Auto-Renewal

```php
$autoRenew = $client->domain('example.com')->autoRenew();

$autoRenew->enable();   // bool
$autoRenew->disable();  // bool
```

## Error Handling

All exceptions implement `Porkbun\Exception\ExceptionInterface` for unified catching:

```php
use Porkbun\Exception\ApiException;
use Porkbun\Exception\AuthenticationException;
use Porkbun\Exception\NetworkException;
use Porkbun\Exception\ExceptionInterface;

try {
    $client->domains()->all();
} catch (AuthenticationException $e) {
    // Invalid or missing API credentials (403)
} catch (ApiException $e) {
    // API returned an error (4xx/5xx)
    $e->getStatusCode();
    $e->getRequest();
    $e->getResponse();
} catch (NetworkException $e) {
    // HTTP/connection failure
    $e->getRequest();
} catch (ExceptionInterface $e) {
    // Catch-all for any library exception
}
```

> [!TIP]
> If the default endpoint is unreachable, fall back to `$client->useIpv4Endpoint()`. See [`08-error-handling.php`](examples/08-error-handling.php) for the full pattern.

## Laravel Integration

The package auto-registers via Laravel's package discovery. The service provider is deferred — the client is only instantiated when you use it.

Add credentials to `.env`:

```env
PORKBUN_API_KEY=pk1_your_key
PORKBUN_SECRET_KEY=sk1_your_secret
PORKBUN_ENDPOINT=default   # or 'ipv4' for IPv4-only
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag=porkbun-config
```

### Facade

```php
use Porkbun\Laravel\Facades\Porkbun;

$domains = Porkbun::domains()->all();
$records = Porkbun::domain('example.com')->dns()->all();
```

### Dependency Injection

```php
use Porkbun\Client;

class DnsController
{
    public function index(Client $client)
    {
        return $client->domain('example.com')->dns()->all();
    }
}
```

## Examples

See the [`examples/`](examples/) directory for runnable scripts:

- [`01-ping.php`](examples/01-ping.php) - Auth test and IPv4 endpoint switching
- [`02-pricing.php`](examples/02-pricing.php) - Public pricing API, cheapest TLDs, iteration
- [`03-domains.php`](examples/03-domains.php) - List domains, pagination, expiring soon, availability check
- [`04-dns.php`](examples/04-dns.php) - DNS CRUD with direct methods, collection helpers
- [`05-dns-builder.php`](examples/05-dns-builder.php) - Fluent builder, convenience methods, immutable templates
- [`06-dns-batch.php`](examples/06-dns-batch.php) - Batch operations, mixed create/edit/delete, rollback
- [`07-domain-services.php`](examples/07-domain-services.php) - Nameservers, URL forwarding, glue records, SSL, auto-renew
- [`08-error-handling.php`](examples/08-error-handling.php) - Exception hierarchy, endpoint fallback pattern
- [`09-dynamic-dns.php`](examples/09-dynamic-dns.php) - Real-world dynamic DNS updater recipe
- [`10-multi-account.php`](examples/10-multi-account.php) - Account switching, public/auth/clearAuth flow
- [`11-laravel.php`](examples/11-laravel.php) - Facade usage, dependency injection, Artisan commands

## Development

```bash
composer install
composer run check    # code style + static analysis + tests
composer run fix      # auto-fix style issues
composer run test     # run test suite
```

## License

MIT License. See [LICENSE](LICENSE) for details.
