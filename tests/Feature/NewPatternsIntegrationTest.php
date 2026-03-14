<?php

declare(strict_types=1);

use Porkbun\Api\Dns;
use Porkbun\Api\Pricing;
use Porkbun\Builder\DnsBatchBuilder;
use Porkbun\DTO\BatchOperationResult;
use Porkbun\DTO\CreateResult;
use Porkbun\DTO\DnsRecord;
use Porkbun\DTO\DnsRecordCollection;
use Porkbun\DTO\PricingCollection;

test('integration - dns service with builder pattern', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 123456],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dnsRecordBuilder = $dns->record();
    $createResult = $dns->createFromBuilder(
        $dnsRecordBuilder->name('www')->a('192.0.2.1')->ttl(3600)->notes('Web server')
    );

    expect($createResult)->toBeInstanceOf(CreateResult::class)
        ->and($createResult->id)->toBe(123456);
});

test('integration - dns service batch operations', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 123],
        ['status' => 'SUCCESS'],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $batch = new DnsBatchBuilder();
    $results = $batch
        ->addRecord('A', 'www', '192.0.2.1')
        ->updateRecord(456, 'A', 'www', '192.0.2.1', ttl: 7200)
        ->execute($dns);

    expect($results)->toHaveCount(2);

    expect($results->items()[0])->toBeInstanceOf(BatchOperationResult::class)
        ->and($results->items()[0]->success)->toBeTrue();

    expect($results->items()[1])->toBeInstanceOf(BatchOperationResult::class)
        ->and($results->items()[1]->success)->toBeTrue();
});

test('integration - pricing service with response objects', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'pricing' => [
                'com' => ['registration' => '8.68', 'renewal' => '8.68'],
                'net' => ['registration' => '9.98', 'renewal' => '9.98'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient);
    $pricing = new Pricing(createMockContext($httpClient));

    $pricingCollection = $pricing->all();

    expect($pricingCollection)->toBeInstanceOf(PricingCollection::class)
        ->and($pricingCollection->has('com'))->toBeTrue()
        ->and($pricingCollection->find('com')?->registrationPrice)->toBe(8.68)
        ->and($pricingCollection->tlds())->toBe(['com', 'net']);
});

test('integration - dns service with typed requests', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 789123],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $createResult = $dns->create(
        'A',
        'api',
        '198.51.100.1',
        7200,
        0,
        'API server'
    );

    expect($createResult)->toBeInstanceOf(CreateResult::class)
        ->and($createResult->id)->toBe(789123)
        ->and($createResult->hasValidId)->toBeTrue();
});

test('integration - dns service all with response objects', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'records' => [
                ['id' => '123', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1', 'ttl' => '600', 'prio' => '0'],
                ['id' => '124', 'name' => 'api', 'type' => 'A', 'content' => '198.51.100.1', 'ttl' => '600', 'prio' => '0'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dnsRecordCollection = $dns->all();

    expect($dnsRecordCollection)->toBeInstanceOf(DnsRecordCollection::class)
        ->and($dnsRecordCollection->count())->toBe(2);

    $record = $dnsRecordCollection->find(123);
    expect($record)->not()->toBeNull();
    if ($record instanceof DnsRecord) {
        expect($record->name)->toBe('www');
    }

    expect($dnsRecordCollection->byType('A'))->toHaveCount(2);
});
