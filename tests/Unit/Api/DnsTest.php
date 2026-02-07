<?php

declare(strict_types=1);

use Porkbun\Api\Dns;
use Porkbun\Builder\DnsRecordBuilder;
use Porkbun\DTO\CreateDnsRecordData;
use Porkbun\DTO\DnsRecord;
use Porkbun\DTO\DnsRecordCollection;
use Porkbun\DTO\DnssecRecord;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

test('dns api can create record', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 123456],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns($httpClient, 'example.com');

    $createDnsRecordData = $dns->create('www', 'A', '192.0.2.1', 3600);

    expect($createDnsRecordData)->toBeInstanceOf(CreateDnsRecordData::class)
        ->and($createDnsRecordData->id)->toBe(123456)
        ->and($createDnsRecordData->hasValidId())->toBeTrue();
});

test('dns api can create record from builder', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 789],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns($httpClient, 'example.com');

    $dnsRecordBuilder = new DnsRecordBuilder()
        ->name('api')
        ->a('10.0.0.1')
        ->ttl(7200);

    $createDnsRecordData = $dns->createFromBuilder($dnsRecordBuilder);

    expect($createDnsRecordData->id)->toBe(789);
});

test('dns api can retrieve records', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'records' => [
                ['id' => '1', 'name' => 'www.example.com', 'type' => 'A', 'content' => '192.0.2.1', 'ttl' => '600', 'prio' => '0'],
                ['id' => '2', 'name' => 'mail.example.com', 'type' => 'MX', 'content' => 'mail.example.com', 'ttl' => '600', 'prio' => '10'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns($httpClient, 'example.com');

    $dnsRecordCollection = $dns->retrieve();

    expect($dnsRecordCollection)->toBeInstanceOf(DnsRecordCollection::class)
        ->and($dnsRecordCollection->count())->toBe(2)
        ->and($dnsRecordCollection->isNotEmpty())->toBeTrue();
});

test('dns api can retrieve record by id', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'records' => [
                ['id' => '1', 'name' => 'www.example.com', 'type' => 'A', 'content' => '192.0.2.1', 'ttl' => '600', 'prio' => '0'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns($httpClient, 'example.com');

    $dnsRecordCollection = $dns->retrieve(1);

    $first = $dnsRecordCollection->first();
    assert($first instanceof DnsRecord);
    expect($dnsRecordCollection->count())->toBe(1)
        ->and($first->id)->toBe(1);
});

test('dns api can retrieve records by type', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'records' => [
                ['id' => '1', 'name' => 'www.example.com', 'type' => 'A', 'content' => '192.0.2.1', 'ttl' => '600', 'prio' => '0'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns($httpClient, 'example.com');

    $dnsRecordCollection = $dns->retrieveByType('A', 'www');

    expect($dnsRecordCollection->count())->toBe(1);
});

test('dns api can edit record', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/dns/edit/example.com/123');

            $body = json_decode((string) $request->getBody(), true);
            expect($body)->toHaveKey('content', '192.0.2.2');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $dns = new Dns($httpClient, 'example.com');

    $dns->edit(123, ['content' => '192.0.2.2']);
});

test('dns api can update by type and name', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/dns/editByNameType/example.com/A/www');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $dns = new Dns($httpClient, 'example.com');

    $dns->update('A', 'www', ['content' => '192.0.2.3']);
});

test('dns api can delete record', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/dns/delete/example.com/123');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $dns = new Dns($httpClient, 'example.com');

    $dns->delete(123);
});

test('dns api can delete by type', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/dns/deleteByNameType/example.com/A/www');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $dns = new Dns($httpClient, 'example.com');

    $dns->deleteByType('A', 'www');
});

test('dns api can create dnssec record', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/dns/createDnssecRecord/example.com');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $dns = new Dns($httpClient, 'example.com');

    $dns->createDnssec([
        'keyTag' => '12345',
        'alg' => '13',
        'digestType' => '2',
        'digest' => 'abc123',
    ]);
});

test('dns api can get dnssec records', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'records' => [
                ['keyTag' => '12345', 'algorithm' => '13', 'digestType' => '2', 'digest' => 'abc123'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns($httpClient, 'example.com');

    $result = $dns->getDnssecRecords();

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(1)
        ->and($result[0])->toBeInstanceOf(DnssecRecord::class)
        ->and($result[0]->keyTag)->toBe(12345);
});

test('dns api can delete dnssec record', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/dns/deleteDnssecRecord/example.com/12345');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $dns = new Dns($httpClient, 'example.com');

    $dns->deleteDnssec('12345');
});

test('dns api returns correct domain', function (): void {
    $mockClient = createMockHttpClient([]);

    $httpClient = createHttpClient($mockClient);
    $dns = new Dns($httpClient, 'example.com');

    expect($dns->getDomain())->toBe('example.com');
});

test('dns api provides record builder', function (): void {
    $mockClient = createMockHttpClient([]);

    $httpClient = createHttpClient($mockClient);
    $dns = new Dns($httpClient, 'example.com');

    expect($dns->record())->toBeInstanceOf(DnsRecordBuilder::class);
});
