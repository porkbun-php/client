<?php

declare(strict_types=1);

use Porkbun\Api\Dns;
use Porkbun\Builder\DnsRecordBuilder;
use Porkbun\DTO\CreateResult;
use Porkbun\DTO\DnsRecord;
use Porkbun\DTO\DnsRecordCollection;
use Porkbun\DTO\DnssecRecord;
use Porkbun\DTO\DnssecRecordCollection;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

test('dns api can create record', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 123456],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $createResult = $dns->create('www', 'A', '192.0.2.1', 3600);

    expect($createResult)->toBeInstanceOf(CreateResult::class)
        ->and($createResult->id)->toBe(123456)
        ->and($createResult->hasValidId())->toBeTrue();
});

test('dns api can create record from builder', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 789],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dnsRecordBuilder = new DnsRecordBuilder()
        ->name('api')
        ->a('10.0.0.1')
        ->ttl(7200);

    $createResult = $dns->createFromBuilder($dnsRecordBuilder);

    expect($createResult->id)->toBe(789);
});

test('dns api can get all records', function (): void {
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
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dnsRecordCollection = $dns->all();

    expect($dnsRecordCollection)->toBeInstanceOf(DnsRecordCollection::class)
        ->and($dnsRecordCollection->count())->toBe(2)
        ->and($dnsRecordCollection->isNotEmpty())->toBeTrue();
});

test('dns api captures cloudflare status', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'cloudflare' => 'enabled',
            'records' => [
                ['id' => '1', 'name' => 'example.com', 'type' => 'A', 'content' => '192.0.2.1', 'ttl' => '600', 'prio' => '0'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dnsRecordCollection = $dns->all();

    expect($dnsRecordCollection->getCloudflare())->toBe('enabled')
        ->and($dnsRecordCollection->isCloudflareEnabled())->toBeTrue();
});

test('dns api handles missing cloudflare status', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'records' => [
                ['id' => '1', 'name' => 'example.com', 'type' => 'A', 'content' => '192.0.2.1', 'ttl' => '600', 'prio' => '0'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dnsRecordCollection = $dns->all();

    expect($dnsRecordCollection->getCloudflare())->toBeNull()
        ->and($dnsRecordCollection->isCloudflareEnabled())->toBeFalse();
});

test('dns api can find record by id', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'records' => [
                ['id' => '1', 'name' => 'www.example.com', 'type' => 'A', 'content' => '192.0.2.1', 'ttl' => '600', 'prio' => '0'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $record = $dns->find(1);

    assert($record instanceof DnsRecord);
    expect($record)->toBeInstanceOf(DnsRecord::class)
        ->and($record->id)->toBe(1);
});

test('dns api find returns null for missing record', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'records' => []],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    expect($dns->find(999))->toBeNull();
});

test('dns api can find records by type', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'records' => [
                ['id' => '1', 'name' => 'www.example.com', 'type' => 'A', 'content' => '192.0.2.1', 'ttl' => '600', 'prio' => '0'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dnsRecordCollection = $dns->findByType('A', 'www');

    expect($dnsRecordCollection->count())->toBe(1);
});

test('dns api findByType captures cloudflare status', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'cloudflare' => 'enabled',
            'records' => [
                ['id' => '28444116', 'name' => 'example.com', 'type' => 'TXT', 'content' => 'v=spf1 mx include:_spf.porkbun.com ~all', 'ttl' => '600', 'prio' => null],
                ['id' => '30832222', 'name' => 'example.com', 'type' => 'TXT', 'content' => 'yandex-verification: abc123', 'ttl' => '600', 'prio' => '0'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dnsRecordCollection = $dns->findByType('TXT');

    expect($dnsRecordCollection->count())->toBe(2)
        ->and($dnsRecordCollection->getCloudflare())->toBe('enabled')
        ->and($dnsRecordCollection->isCloudflareEnabled())->toBeTrue();
});

test('dns api findByType with subdomain', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/dns/retrieveByNameType/example.com/TXT/mail._domainkey');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode([
            'status' => 'SUCCESS',
            'cloudflare' => 'enabled',
            'records' => [
                ['id' => '30832254', 'name' => 'mail._domainkey.example.com', 'type' => 'TXT', 'content' => 'v=DKIM1; k=rsa; p=...', 'ttl' => '21600', 'prio' => '0'],
            ],
        ])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dnsRecordCollection = $dns->findByType('TXT', 'mail._domainkey');

    expect($dnsRecordCollection->count())->toBe(1);

    $first = $dnsRecordCollection->first();
    assert($first instanceof DnsRecord);
    expect($first->name)->toBe('mail._domainkey.example.com');
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
    $dns = new Dns(createMockContext($httpClient), 'example.com');

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
    $dns = new Dns(createMockContext($httpClient), 'example.com');

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
    $dns = new Dns(createMockContext($httpClient), 'example.com');

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
    $dns = new Dns(createMockContext($httpClient), 'example.com');

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
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dns->createDnssec([
        'keyTag' => '12345',
        'alg' => '13',
        'digestType' => '2',
        'digest' => 'abc123',
    ]);
});

test('dns api can get dnssec records', function (): void {
    // Real API returns records as associative object keyed by keyTag
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'records' => [
                '2371' => [
                    'keyTag' => '2371',
                    'alg' => '13',
                    'digestType' => '2',
                    'digest' => '40A829ECBBC0ABBDD8DCB23526BE8FD25A2D6F49E70260BDB101A42AF6F35E07',
                ],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dnssecRecordCollection = $dns->getDnssecRecords();

    expect($dnssecRecordCollection)->toBeInstanceOf(DnssecRecordCollection::class)
        ->and($dnssecRecordCollection)->toHaveCount(1);

    $record = $dnssecRecordCollection->first();
    assert($record instanceof DnssecRecord);
    expect($record->keyTag)->toBe(2371)
        ->and($record->algorithm)->toBe(13)
        ->and($record->digestType)->toBe(2)
        ->and($record->digest)->toBe('40A829ECBBC0ABBDD8DCB23526BE8FD25A2D6F49E70260BDB101A42AF6F35E07')
        ->and($record->getAlgorithmName())->toBe('ECDSAP256SHA256')
        ->and($record->getDigestTypeName())->toBe('SHA-256');
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
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dns->deleteDnssec('12345');
});

test('dns api provides record builder', function (): void {
    $mockClient = createMockHttpClient([]);

    $httpClient = createHttpClient($mockClient);
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    expect($dns->record())->toBeInstanceOf(DnsRecordBuilder::class);
});
