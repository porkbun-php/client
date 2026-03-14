# Porkbun PHP API Client

[![Tests](https://github.com/porkbun-php/client/actions/workflows/tests.yml/badge.svg)](https://github.com/porkbun-php/client/actions/workflows/tests.yml)
[![Code Quality](https://github.com/porkbun-php/client/actions/workflows/code-quality.yml/badge.svg)](https://github.com/porkbun-php/client/actions/workflows/code-quality.yml)
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
- **Strict quality** — Pint, Pest, PHPStan, PHP 8.4+ with Rector

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
$cert = $domain->ssl();
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
```

### Ping (Auth Test)

```php
$ping = $client->ping();

$ping->resolvedIp;  // Your IP address (prefers forwarded IP)
$ping->forwardedIp; // Forwarded IP (from X-Forwarded-For header)
$ping->yourIp;      // Raw IP from API response
```

### Domains

```php
// List all domains (iterates all pages automatically)
foreach ($client->domains()->all() as $domain) {
    echo "{$domain->domain} expires {$domain->expireDate?->format('Y-m-d')}\n";
}

// Single page with pagination metadata
$page = $client->domains()->list();
$page->domains(); // DomainCollection (also available via iteration/count/json on $page itself)
$page->hasMore;    // bool — true if more pages exist
$page->nextStart;  // ?int — pass to list() for the next page
$page->start;      // int — current offset

// PaginatedResult is iterable, countable, and JSON-serializable:
count($page);              // number of domains on this page
json_encode($page);        // serializes with pagination metadata
foreach ($page as $domain) { /* ... */ }

// Paginate manually
$page = $client->domains()->list(start: 0, includeLabels: true);
while ($page->hasMore) {
    $page = $client->domains()->list(start: $page->nextStart);
}

// Find a specific domain
$domain = $client->domains()->find('example.com'); // Domain DTO or null

// Bulk auto-renewal management
$client->domains()->enableAutoRenew('example.com', 'other.com');
$client->domains()->disableAutoRenew('example.com');
```

### Domain Details

```php
$domain = $client->domain('example.com');
$info = $domain->details();      // Domain DTO (from your account)

$info->domain;                   // 'example.com'
$info->status;                   // 'ACTIVE'
$info->expireDate;               // ?DateTimeImmutable
$info->autoRenew;                // ?bool
$info->tld;                      // 'com'
```

### Domain Availability

```php
$result = $client->domain('example.com')->check();

$result->isAvailable;           // bool
$result->price;                 // ?float (registration price)
$result->type;                  // string — 'standard', 'premium', etc.
$result->priceInCents;          // ?int (e.g., 999 for $9.99)
$result->effectivePrice;        // ?float (promo price if available, else regular)
```

### Domain Registration

```php
$result = $client->domain('newdomain.com')->register(868);

$result->domain;                 // 'newdomain.com'
$result->orderId;                // int
$result->costInCents;            // int
$result->costInDollars;          // float (computed)
$result->balanceInCents;         // int
$result->balanceInDollars;       // float (computed)
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
// Create (direct)
$result = $dns->create('A', 'www', '192.0.2.1', ttl: 3600);
echo "Created record: {$result->id}";

// Create (builder — recommended)
$result = $dns->createFromBuilder(
    $dns->record()
        ->a('192.0.2.2')
        ->name('api')
        ->ttl(3600)
        ->notes('API server')
);

// Builder convenience methods
$dns->record()->mx('mail.provider.com', priority: 10)->name('mail');
$dns->record()->txt('v=DMARC1; p=reject')->name('_dmarc');
$dns->record()->cname('blog.provider.com')->name('blog');
$dns->record()->aaaa('2001:db8::1')->name('app');

// Enum types are also accepted
use Porkbun\Enum\DnsRecordType;
$dns->findByType(DnsRecordType::A);
$dns->create(DnsRecordType::A, 'www', '192.0.2.1');

// Update (direct or builder)
$dns->update($recordId, 'A', 'www', '192.0.2.3');
$dns->updateFromBuilder($recordId, $dns->record()->a('192.0.2.3')->name('www'));
$dns->updateByType('A', 'www', '192.0.2.3');

// Delete
$dns->delete($recordId);
$dns->deleteByType('A', 'old-subdomain');

// Collection helpers (all collections support first(), last(), count())
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
$result = $dnssec->create(keyTag: 12345, algorithm: 13, digestType: 2, digest: 'abc123...');
$result->message;          // ?string
$dnssec->delete($keyTag);
```

### Batch DNS Operations

```php
$dns = $client->domain('example.com')->dns();

// Pre-wired batch builder — no need to pass $dns to execute()
$results = $dns->batch()
    ->addRecord('A', 'www', '192.0.2.1')
    ->addRecord('A', 'api', '192.0.2.2')
    ->add($dns->record()->mx('mail.example.com', priority: 10))  // builder-based add
    ->updateRecord($existingId, 'A', 'www', '192.0.2.3', ttl: 3600)
    ->deleteRecord($oldRecordId)
    ->deleteByType('TXT', 'old-subdomain')
    ->execute();

if ($results->hasFailures()) {
    echo "Some operations failed!\n";
}

foreach ($results as $result) {
    if ($result->success) {
        echo "OK: {$result->operation->value}\n";
    } else {
        echo "Failed: {$result->error}\n";
    }
}
```

### SSL Certificates

```php
$cert = $client->domain('example.com')->ssl();

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
$ns->update('ns1.custom.com', 'ns2.custom.com');
```

### URL Forwarding

```php
$forwards = $client->domain('example.com')->urlForwarding();

$forwards->all();              // UrlForwardCollection
$result = $forwards->create('https://destination.example.com', 'temporary', subdomain: 'go');
$result->message;              // ?string
$forwards->delete($recordId);
```

### Glue Records

```php
$glue = $client->domain('example.com')->glueRecords();

$glue->all();              // GlueRecordCollection
$result = $glue->create('ns1', '192.0.2.1', '192.0.2.2');
$result->message;          // ?string
$glue->update('ns1', '192.0.2.10');
$glue->delete('ns1');      // OperationResult
```

### Auto-Renewal

```php
$autoRenew = $client->domain('example.com')->autoRenew();

$result = $autoRenew->enable();   // AutoRenewResult
$result->success;                 // bool
$result->message;                 // ?string

$autoRenew->disable();
```

## Error Handling

All exceptions implement `Porkbun\Exception\ExceptionInterface` for unified catching:

```php
use Porkbun\Exception\ApiException;
use Porkbun\Exception\AuthenticationException;
use Porkbun\Exception\InvalidArgumentException;
use Porkbun\Exception\NetworkException;
use Porkbun\Exception\ExceptionInterface;

try {
    $client->domains()->list();
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
} catch (InvalidArgumentException $e) {
    // Invalid parameters (bad DNS type, empty domain list, etc.)
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

$domains = Porkbun::domains()->list();
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
- [`06-dns-batch.php`](examples/06-dns-batch.php) - Batch operations, mixed create/edit/delete
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
