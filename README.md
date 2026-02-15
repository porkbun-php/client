# Porkbun PHP API Client

A modern PHP client for the [Porkbun API](https://porkbun.com/api/json/v3/documentation) with full endpoint coverage.

## Features

- Complete Porkbun API v3 coverage (22 endpoints)
- Domain-centric API design
- PSR-18 HTTP client support with auto-discovery
- Immutable DTOs with factory methods
- Fluent builders for DNS records
- Laravel integration included
- PHP 8.4+ with strict typing

## Requirements

- PHP 8.4 or higher
- PSR-18 HTTP client (auto-discovered or manual)

## Installation

```bash
composer require glebovdev/porkbun-php-api
```

### HTTP Client

This package requires a PSR-18 HTTP client. If you already have Guzzle or Symfony HttpClient installed, you're all set - the package auto-discovers it.

Otherwise, install one:

```bash
composer require symfony/http-client nyholm/psr7
```

## Quick Start

```php
use Porkbun\Client;

// Create client (credentials optional for public endpoints)
$client = Client::create('pk1_your_api_key', 'sk1_your_secret_key');

// Check API connectivity
$ping = $client->ping();
echo "Your IP: " . $ping->ip();

// Get domain pricing (no auth required)
$pricing = $client->pricing()->all();
echo "COM registration: $" . $pricing->get('com')?->registrationPrice;

// Domain-centric operations
$domain = $client->domain('example.com');

$records = $domain->dns()->all();
foreach ($records as $record) {
    echo "{$record->name} {$record->dnsRecordType->value} {$record->content}\n";
}
```

## API Reference

### Client Configuration

```php
// Without authentication (for public endpoints)
$client = Client::create();

// With authentication
$client = Client::create($apiKey, $secretKey);

// Dynamic authentication
$client->authenticate($apiKey, $secretKey);
$client->clearAuth();

// Switch endpoints
$client->useIpv4Endpoint();    // IPv4-only endpoint
$client->useDefaultEndpoint(); // Default dual-stack
```

### Pricing (Public)

```php
$pricing = $client->pricing()->all();

$pricing->get('com');                          // PricingItem or null
$pricing->get('com')?->registrationPrice;      // float or null
$pricing->get('com')?->renewalPrice;           // float or null
$pricing->cheapest(10);                        // Top 10 cheapest TLDs
$pricing->tlds();                              // All available TLDs
```

### Global Domain Operations

```php
$domains = $client->domains();

$domains->all();                   // List all domains in account
$domains->check('example.com');    // Check domain availability
```

### Domain-Centric Operations

```php
$domain = $client->domain('example.com');

// DNS records
$domain->dns()->all();
$domain->dns()->find($recordId);
$domain->dns()->create('www', 'A', '192.0.2.1');

// SSL certificate
$domain->ssl()->get();

// Nameservers
$domain->nameservers()->all();
$domain->nameservers()->update(['ns1.porkbun.com', 'ns2.porkbun.com']);

// URL forwarding
$domain->urlForwarding()->all();
$domain->urlForwarding()->add([...]);
$domain->urlForwarding()->delete($recordId);

// Glue records
$domain->glue()->all();
$domain->glue()->create('ns1', ['192.0.2.1']);
$domain->glue()->update('ns1', ['192.0.2.2']);
$domain->glue()->delete('ns1');
```

### DNS Records

```php
$dns = $client->domain('example.com')->dns();

// Create records
$result = $dns->create('www', 'A', '192.0.2.1', ttl: 3600);
echo "Created record ID: " . $result->id;

// Using the builder (recommended)
$result = $dns->createFromBuilder(
    $dns->record()
        ->name('api')
        ->a('192.0.2.2')
        ->ttl(3600)
        ->notes('API server')
);

// Builder convenience methods
$dns->record()->mx('mail.example.com', priority: 10);
$dns->record()->txt('v=spf1 include:_spf.example.com ~all');
$dns->record()->cname('cdn.provider.com');

// Retrieve records
$records = $dns->all();                   // All records
$record = $dns->find($recordId);          // Single record
$records = $dns->findByType('A');         // By type
$records = $dns->findByType('A', 'www');  // By type and name
$record = $dns->first();                  // First record
$record = $dns->last();                   // Last record

// Filter collections
$records->getRecordsByType('MX');
$records->getRecordsByName('www');
$records->getRootRecords();
$records->first();
$records->firstOfType('A');

// Update records
$dns->edit($recordId, ['content' => '192.0.2.3']);
$dns->update('A', 'www', ['content' => '192.0.2.3']); // Updates ALL matching

// Delete records
$dns->delete($recordId);
$dns->deleteByType('A', 'old-subdomain');

// DNSSEC
$dns->createDnssec([...]);
$dns->getDnssecRecords();
$dns->deleteDnssec($keyTag);
```

### Batch Operations

```php
use Porkbun\Builder\DnsBatchBuilder;

$dns = $client->domain('example.com')->dns();
$batch = new DnsBatchBuilder();

$results = $batch
    ->addRecord('www', 'A', '192.0.2.1')
    ->addRecord('api', 'A', '192.0.2.2')
    ->editRecord($existingId, ['ttl' => '3600'])
    ->deleteRecord($oldRecordId)
    ->execute($dns);

foreach ($results as $result) {
    if ($result->isSuccess()) {
        echo "Success: {$result->operation}";
    } else {
        echo "Failed: {$result->error}";
    }
}
```

### SSL Certificates

```php
$ssl = $client->domain('example.com')->ssl();
$cert = $ssl->get();

$cert->certificateChain;
$cert->privateKey;
$cert->publicKey;
$cert->getFullChain(); // Chain + intermediate
```

## Laravel Integration

### Configuration

Publish the configuration:

```bash
php artisan vendor:publish --tag=porkbun-config
```

Set environment variables:

```env
PORKBUN_API_KEY=pk1_your_key
PORKBUN_SECRET_KEY=sk1_your_secret
PORKBUN_ENDPOINT=default
```

### Usage

```php
use Porkbun\Laravel\Facades\Porkbun;

// Using the facade
$domains = Porkbun::domains()->all();
$records = Porkbun::domain('example.com')->dns()->all();

// Using dependency injection
public function __construct(private \Porkbun\Client $porkbun) {}
```

## Error Handling

```php
use Porkbun\Exception\ApiException;
use Porkbun\Exception\AuthenticationException;
use Porkbun\Exception\NetworkException;

try {
    $client->domains()->all();
} catch (AuthenticationException $e) {
    // Invalid or missing credentials
} catch (ApiException $e) {
    // API returned an error
    $e->getStatusCode();
    $e->getRequest();
    $e->getResponse();
} catch (NetworkException $e) {
    // Connection failed
}
```

## License

MIT License. See [LICENSE](LICENSE) for details.
