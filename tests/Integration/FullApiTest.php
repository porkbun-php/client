<?php

declare(strict_types=1);

use Porkbun\Builder\DnsBatchBuilder;
use Porkbun\Client;
use Porkbun\DTO\AutoRenewResult;
use Porkbun\DTO\AvailabilityResult;
use Porkbun\DTO\CreateResult;
use Porkbun\DTO\DnsRecord;
use Porkbun\DTO\DnsRecordCollection;
use Porkbun\DTO\DnssecRecordCollection;
use Porkbun\DTO\Domain;
use Porkbun\DTO\DomainCollection;
use Porkbun\DTO\GlueRecordCollection;
use Porkbun\DTO\NameserverCollection;
use Porkbun\DTO\PaginatedResult;
use Porkbun\DTO\PingResult;
use Porkbun\DTO\PricingCollection;
use Porkbun\DTO\PricingItem;
use Porkbun\DTO\SslCertificate;
use Porkbun\DTO\UrlForward;
use Porkbun\DTO\UrlForwardCollection;
use Porkbun\Enum\BatchOperationType;
use Porkbun\Enum\DnsRecordType;
use Porkbun\Exception\ApiException;

/*
|--------------------------------------------------------------------------
| Integration Test Suite — Real Porkbun API
|--------------------------------------------------------------------------
|
| These tests exercise every public method against the live Porkbun API.
| They are excluded from the default test suite and require env vars:
|
|   PORKBUN_API_KEY    — pk1_xxx API key
|   PORKBUN_SECRET_KEY — sk1_xxx secret key
|   PORKBUN_DOMAIN     — a real domain in the account (e.g. example.com)
|
| Run with:  composer run test:integration
| Or:        PORKBUN_API_KEY=... PORKBUN_SECRET_KEY=... PORKBUN_DOMAIN=... vendor/bin/pest tests/Integration
|
*/

// Skip the entire file if credentials are missing
$apiKey = getenv('PORKBUN_API_KEY') ?: null;
$secretKey = getenv('PORKBUN_SECRET_KEY') ?: null;
$domain = getenv('PORKBUN_DOMAIN') ?: null;

if (!$apiKey || !$secretKey || !$domain) {
    test('integration tests require PORKBUN_API_KEY, PORKBUN_SECRET_KEY, and PORKBUN_DOMAIN env vars')
        ->skip('Missing required environment variables')
        ->group('integration');

    return;
}

// Shared client instance for all tests
$client = new Client();
$client->authenticate($apiKey, $secretKey);
$domainResource = $client->domain($domain);

// --------------------------------------------------------------------------
// Pricing (no auth required)
// --------------------------------------------------------------------------

test('pricing: retrieve all TLD pricing', function () use ($client): void {
    $pricingCollection = $client->pricing()->all();

    expect($pricingCollection)->toBeInstanceOf(PricingCollection::class)
        ->and($pricingCollection->count())->toBeGreaterThan(0)
        ->and($pricingCollection->isEmpty())->toBeFalse()
        ->and($pricingCollection->isNotEmpty())->toBeTrue()
        ->and($pricingCollection->tlds())->toBeArray()->not->toBeEmpty();

    // .com should always exist
    $com = $pricingCollection->find('com');
    expect($com)->toBeInstanceOf(PricingItem::class)
        ->and($com->tld)->toBe('com')
        ->and($com->registrationPrice)->toBeGreaterThan(0)
        ->and($com->renewalPrice)->toBeGreaterThan(0);

    // cheapest() returns items sorted by price
    $cheapest = $pricingCollection->cheapest(5);
    expect($cheapest)->toBeArray()->toHaveCount(5);

    // first/last convenience
    expect($pricingCollection->first())->toBeInstanceOf(PricingItem::class)
        ->and($pricingCollection->last())->toBeInstanceOf(PricingItem::class);
})->group('integration');

// --------------------------------------------------------------------------
// Ping (auth test)
// --------------------------------------------------------------------------

test('ping: verify authentication and IP resolution', function () use ($client): void {
    $ping = $client->ping();

    expect($ping)->toBeInstanceOf(PingResult::class)
        ->and($ping->hasIp)->toBeTrue()
        ->and($ping->resolvedIp)->toBeString()->not->toBeEmpty();
})->group('integration');

// --------------------------------------------------------------------------
// Domain Availability
// --------------------------------------------------------------------------

test('domain availability: check a taken domain', function () use ($domainResource): void {
    $availability = $domainResource->check();

    expect($availability)->toBeInstanceOf(AvailabilityResult::class)
        ->and($availability->isAvailable)->toBeFalse()
        ->and($availability->type)->toBeString();
})->group('integration');

test('domain availability: check an unlikely-available domain', function () use ($client): void {
    // Porkbun rate-limits availability checks (1 per 10 seconds)
    sleep(10);

    $availability = $client->domain('thisisaverylongdomainthatdoesnotexist12345.com')->check();

    expect($availability)->toBeInstanceOf(AvailabilityResult::class)
        ->and($availability->effectivePrice)->not->toBeNull();
})->group('integration');

// --------------------------------------------------------------------------
// Domain List
// --------------------------------------------------------------------------

test('domains: list all domains in account', function () use ($client, $domain): void {
    $paginatedResult = $client->domains()->list();

    expect($paginatedResult)->toBeInstanceOf(PaginatedResult::class)
        ->and($paginatedResult)->toHaveCount($paginatedResult->domains()->count())
        ->and($paginatedResult->domains())->toBeInstanceOf(DomainCollection::class);

    $domainCollection = $paginatedResult->domains();

    expect($domainCollection->count())->toBeGreaterThan(0)
        ->and($domainCollection->has($domain))->toBeTrue();

    $domainDto = $domainCollection->find($domain);
    expect($domainDto)->toBeInstanceOf(Domain::class)
        ->and($domainDto->domain)->toBe($domain)
        ->and($domainDto->status)->toBeString();

    // first/last
    expect($domainCollection->first())->toBeInstanceOf(Domain::class)
        ->and($domainCollection->last())->toBeInstanceOf(Domain::class);
})->group('integration');

test('domains: find returns a single domain', function () use ($client, $domain): void {
    $domainDto = $client->domains()->find($domain);

    expect($domainDto)->toBeInstanceOf(Domain::class)
        ->and($domainDto->domain)->toBe($domain)
        ->and($domainDto->status)->toBeString();
})->group('integration');

test('domains: find returns null for unknown domain', function () use ($client): void {
    $domainDto = $client->domains()->find('this-domain-definitely-does-not-exist-12345.com');

    expect($domainDto)->toBeNull();
})->group('integration');

test('domain: details returns domain DTO with metadata', function () use ($domainResource, $domain): void {
    $details = $domainResource->details();

    expect($details)->toBeInstanceOf(Domain::class)
        ->and($details->domain)->toBe($domain)
        ->and($details->status)->toBeString()->not->toBeEmpty()
        ->and($details->autoRenew)->toBeBool()
        ->and($details->securityLock)->toBeBool()
        ->and($details->whoisPrivacy)->toBeBool()
        ->and($details->expireDate)->toBeInstanceOf(DateTimeImmutable::class);
})->group('integration');

test('domains: all generator yields domains', function () use ($client, $domain): void {
    $found = false;

    foreach ($client->domains()->all() as $domainDto) {
        expect($domainDto)->toBeInstanceOf(Domain::class);

        if ($domainDto->domain === $domain) {
            $found = true;
        }
    }

    expect($found)->toBeTrue();
})->group('integration');

// --------------------------------------------------------------------------
// DNS CRUD Lifecycle
// --------------------------------------------------------------------------

test('dns: full CRUD lifecycle', function () use ($domainResource): void {
    $dns = $domainResource->dns();

    // Clean up leftover records from previous failed runs
    try {
        $dns->deleteByType('TXT', '_test-integ');
    } catch (ApiException) {
        // No leftover records — fine
    }

    // --- Create via direct method ---
    $createResult = $dns->create('TXT', '_test-integ', 'porkbun-php-test-direct', 600);

    expect($createResult)->toBeInstanceOf(CreateResult::class)
        ->and($createResult->id)->toBeGreaterThan(0)
        ->and($createResult->hasValidId)->toBeTrue();

    $recordId = $createResult->id;

    // --- Find by ID ---
    $record = $dns->find($recordId);

    expect($record)->toBeInstanceOf(DnsRecord::class)
        ->and($record->id)->toBe($recordId)
        ->and($record->type)->toBe(DnsRecordType::TXT)
        ->and($record->content)->toBe('porkbun-php-test-direct')
        ->and($record->ttl)->toBe(600);

    // --- Find by type ---
    $dnsRecordCollection = $dns->findByType('TXT', '_test-integ');

    expect($dnsRecordCollection)->toBeInstanceOf(DnsRecordCollection::class)
        ->and($dnsRecordCollection->count())->toBeGreaterThanOrEqual(1);

    $found = $dnsRecordCollection->find($recordId);
    expect($found)->not->toBeNull();

    // --- Edit by ID (Porkbun requires name, type, content in edit payload) ---
    $dns->update($recordId, 'TXT', '_test-integ', 'porkbun-php-test-edited', ttl: 300);

    $edited = $dns->find($recordId);
    expect($edited->content)->toBe('porkbun-php-test-edited')
        ->and($edited->ttl)->toBe(300);

    // --- Delete by ID ---
    $dns->delete($recordId);

    // Verify deletion
    $deleted = $dns->find($recordId);
    expect($deleted)->toBeNull();
})->group('integration');

test('dns: create via builder and delete by type', function () use ($domainResource): void {
    $dns = $domainResource->dns();

    // --- Create via builder ---
    $createResult = $dns->createFromBuilder(
        $dns->record()
            ->name('_test-builder')
            ->txt('porkbun-php-test-builder')
            ->ttl(600)
    );

    expect($createResult)->toBeInstanceOf(CreateResult::class)
        ->and($createResult->id)->toBeGreaterThan(0);

    // --- Verify it exists ---
    $dnsRecordCollection = $dns->findByType(DnsRecordType::TXT, '_test-builder');
    expect($dnsRecordCollection->count())->toBeGreaterThanOrEqual(1);

    // --- Delete by type/name ---
    $dns->deleteByType('TXT', '_test-builder');

    // --- Verify deletion ---
    $afterDelete = $dns->findByType('TXT', '_test-builder');
    expect($afterDelete->count())->toBe(0);
})->group('integration');

test('dns: update by type/name', function () use ($domainResource): void {
    $dns = $domainResource->dns();

    // Create a record to update
    $createResult = $dns->create('TXT', '_test-update', 'before-update', 600);
    $recordId = $createResult->id;

    try {
        // Update by type + name
        $dns->updateByType('TXT', '_test-update', 'after-update');

        $updated = $dns->find($recordId);
        expect($updated->content)->toBe('after-update');
    } finally {
        // Cleanup
        $dns->delete($recordId);
    }
})->group('integration');

test('dns: all() returns collection', function () use ($domainResource): void {
    $dns = $domainResource->dns();
    $all = $dns->all();

    expect($all)->toBeInstanceOf(DnsRecordCollection::class)
        ->and($all->count())->toBeGreaterThanOrEqual(0);

    // Iterate
    foreach ($all as $record) {
        expect($record)->toBeInstanceOf(DnsRecord::class)
            ->and($record->id)->toBeGreaterThan(0)
            ->and($record->type)->toBeInstanceOf(DnsRecordType::class);
    }
})->group('integration');

test('dns: batch operations', function () use ($domainResource): void {
    $dns = $domainResource->dns();

    // Create two records via batch
    $batch = new DnsBatchBuilder();
    $results = $batch
        ->add($dns->record()->name('_test-batch1')->txt('batch-value-1'))
        ->add($dns->record()->name('_test-batch2')->txt('batch-value-2'))
        ->execute($dns);

    expect($results)->toHaveCount(2);

    $createdIds = [];

    foreach ($results as $result) {
        expect($result->success)->toBeTrue()
            ->and($result->operation)->toBe(BatchOperationType::CREATE);

        if ($result->recordId !== null) {
            $createdIds[] = $result->recordId;
        }
    }

    // Cleanup
    foreach ($createdIds as $createdId) {
        $dns->delete($createdId);
    }
})->group('integration');

// --------------------------------------------------------------------------
// DNSSEC (read-only — creating requires valid DS records)
// --------------------------------------------------------------------------

test('dnssec: retrieve records', function () use ($domainResource): void {
    $dnssec = $domainResource->dnssec();
    $dnssecRecordCollection = $dnssec->all();

    expect($dnssecRecordCollection)->toBeInstanceOf(DnssecRecordCollection::class)
        ->and($dnssecRecordCollection->count())->toBeGreaterThanOrEqual(0);
})->group('integration');

// --------------------------------------------------------------------------
// SSL
// --------------------------------------------------------------------------

test('ssl: retrieve certificate bundle', function () use ($domainResource): void {
    try {
        $cert = $domainResource->ssl();

        expect($cert)->toBeInstanceOf(SslCertificate::class);

        // If a cert exists, it should have content
        if ($cert->hasCertificate) {
            expect($cert->certificateChain)->not->toBeEmpty()
                ->and($cert->privateKey)->not->toBeEmpty();
        }
    } catch (ApiException $e) {
        // No SSL cert provisioned for this domain — acceptable
        expect($e->getStatusCode())->toBeGreaterThanOrEqual(400);
    }
})->group('integration');

// --------------------------------------------------------------------------
// Nameservers
// --------------------------------------------------------------------------

test('nameservers: retrieve current nameservers', function () use ($domainResource): void {
    $nameservers = $domainResource->nameservers();
    $nameserverCollection = $nameservers->all();

    expect($nameserverCollection)->toBeInstanceOf(NameserverCollection::class)
        ->and($nameserverCollection->count())->toBeGreaterThan(0)
        ->and($nameserverCollection->isEmpty())->toBeFalse();

    // Each entry is a string
    foreach ($nameserverCollection as $nameserver) {
        expect($nameserver)->toBeString()->not->toBeEmpty();
    }

    expect($nameserverCollection->first())->toBeString()
        ->and($nameserverCollection->last())->toBeString();
})->group('integration');

// --------------------------------------------------------------------------
// URL Forwarding
// --------------------------------------------------------------------------

test('url forwarding: add, list, delete lifecycle', function () use ($domainResource): void {
    $urlForwarding = $domainResource->urlForwarding();

    // Create a test forward
    $urlForwarding->create(
        'https://example.com/test-integration',
        'temporary',
        subdomain: '_test-fwd',
    );

    // List and find our forward
    $all = $urlForwarding->all();
    expect($all)->toBeInstanceOf(UrlForwardCollection::class);

    $found = null;

    foreach ($all as $forward) {
        if (str_contains($forward->subdomain, '_test-fwd')) {
            $found = $forward;

            break;
        }
    }

    expect($found)->not->toBeNull();

    if ($found instanceof UrlForward) {
        expect($found->location)->toBe('https://example.com/test-integration')
            ->and($found->isTemporary)->toBeTrue();

        // Delete it
        $urlForwarding->delete($found->id);
    }

    // Verify deletion
    $urlForwardCollection = $urlForwarding->all();

    foreach ($urlForwardCollection as $forward) {
        expect($forward->subdomain)->not->toContain('_test-fwd');
    }
})->group('integration');

// --------------------------------------------------------------------------
// Glue Records
// --------------------------------------------------------------------------

test('glue records: list existing records', function () use ($domainResource): void {
    $glue = $domainResource->glueRecords();
    $glueRecordCollection = $glue->all();

    expect($glueRecordCollection)->toBeInstanceOf(GlueRecordCollection::class)
        ->and($glueRecordCollection->count())->toBeGreaterThanOrEqual(0);
})->group('integration');

test('glue records: create, update, delete lifecycle', function () use ($domainResource): void {
    $glue = $domainResource->glueRecords();
    $subdomain = 'testns1';

    try {
        $glue->create($subdomain, '192.0.2.1');
    } catch (ApiException $e) {
        // Glue records require custom nameservers — skip if not supported
        $this->markTestSkipped("Glue records not supported on this domain: {$e->getMessage()}");
    }

    try {
        // List and verify
        $all = $glue->all();
        expect($all)->toBeInstanceOf(GlueRecordCollection::class);

        $found = $all->find($subdomain);
        expect($found)->not->toBeNull()
            ->and($found->ips)->toContain('192.0.2.1');

        // Update
        $glue->update($subdomain, '192.0.2.2');

        $afterUpdate = $glue->all();
        $updated = $afterUpdate->find($subdomain);
        expect($updated)->not->toBeNull()
            ->and($updated->ips)->toContain('192.0.2.2');
    } finally {
        $glue->delete($subdomain);
    }

    // Verify deletion
    $glueRecordCollection = $glue->all();
    $deleted = $glueRecordCollection->find($subdomain);
    expect($deleted)->toBeNull();
})->group('integration');

// --------------------------------------------------------------------------
// Auto-Renew
// --------------------------------------------------------------------------

test('auto-renew: disable and re-enable', function () use ($domainResource): void {
    // Read the original auto-renew state so we can restore it
    $domain = $domainResource->details();
    $autoRenew = $domainResource->autoRenew();

    try {
        // Test both methods — disable first, then re-enable to leave domain safe
        $disableResult = $autoRenew->disable();
        $enableResult = $autoRenew->enable();

        expect($disableResult)->toBeInstanceOf(AutoRenewResult::class)
            ->and($disableResult->success)->toBeBool()
            ->and($enableResult)->toBeInstanceOf(AutoRenewResult::class)
            ->and($enableResult->success)->toBeBool();
    } finally {
        // Restore original state
        $domain->autoRenew ? $autoRenew->enable() : $autoRenew->disable();
    }
})->group('integration');

// --------------------------------------------------------------------------
// Domain Registration — SKIPPED (costs real money)
// --------------------------------------------------------------------------

test('domain registration: skipped to avoid real charges')
    ->skip('Domain registration costs real money')
    ->group('integration');
