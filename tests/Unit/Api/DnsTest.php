<?php

declare(strict_types=1);

use Porkbun\Api\Dns;
use Porkbun\Builder\DnsRecordBuilder;
use Porkbun\DTO\CreateResult;
use Porkbun\DTO\DnsRecord;
use Porkbun\DTO\DnsRecordCollection;
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
        ->and($createResult->hasValidId)->toBeTrue();
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

    expect($dnsRecordCollection)->toBeInstanceOf(DnsRecordCollection::class)
        ->and($dns->cloudflare)->toBe('enabled')
        ->and($dns->isCloudflareEnabled)->toBeTrue();
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

    expect($dnsRecordCollection)->toBeInstanceOf(DnsRecordCollection::class)
        ->and($dns->cloudflare)->toBeNull()
        ->and($dns->isCloudflareEnabled)->toBeFalse();
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
        ->and($dns->cloudflare)->toBe('enabled')
        ->and($dns->isCloudflareEnabled)->toBeTrue();
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
    expect($first->name)->toBe('mail._domainkey');
});

test('dns api can update record', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/dns/edit/example.com/123');

            $body = json_decode((string) $request->getBody(), true);
            expect($body)
                ->toHaveKey('name', 'www')
                ->toHaveKey('type', 'A')
                ->toHaveKey('content', '192.0.2.2')
                ->toHaveKey('ttl', '600')
                ->toHaveKey('prio', '0');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dns->update(123, 'www', 'A', '192.0.2.2');
});

test('dns api can update record with optional params', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            $body = json_decode((string) $request->getBody(), true);
            expect($body)
                ->toHaveKey('ttl', '3600')
                ->toHaveKey('prio', '10')
                ->toHaveKey('notes', 'Updated record');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dns->update(123, 'mail', 'MX', 'mail.example.com', ttl: 3600, prio: 10, notes: 'Updated record');
});

test('dns api update sends empty string notes to clear them', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            $body = json_decode((string) $request->getBody(), true);
            expect($body)
                ->toHaveKey('notes', '');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dns->update(123, 'www', 'A', '192.0.2.1', notes: '');
});

test('dns api update omits notes when null', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            $body = json_decode((string) $request->getBody(), true);
            expect($body)->not->toHaveKey('notes');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dns->update(123, 'www', 'A', '192.0.2.1');
});

test('dns api can update record from builder', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/dns/edit/example.com/789');

            $body = json_decode((string) $request->getBody(), true);
            expect($body)
                ->toHaveKey('name', 'api')
                ->toHaveKey('type', 'A')
                ->toHaveKey('content', '10.0.0.1')
                ->toHaveKey('ttl', '7200');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dnsRecordBuilder = new DnsRecordBuilder()
        ->name('api')
        ->a('10.0.0.1')
        ->ttl(7200);

    $dns->updateFromBuilder(789, $dnsRecordBuilder);
});

test('dns api can update by type and name', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/dns/editByNameType/example.com/A/www');

            $body = json_decode((string) $request->getBody(), true);
            expect($body)
                ->toHaveKey('content', '192.0.2.3')
                ->toHaveKey('ttl', '600')
                ->toHaveKey('prio', '0');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dns->updateByType('A', 'www', '192.0.2.3');
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

test('dns api provides record builder', function (): void {
    $mockClient = createMockHttpClient([]);

    $httpClient = createHttpClient($mockClient);
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    expect($dns->record())->toBeInstanceOf(DnsRecordBuilder::class);
});

test('dns api all normalizes FQDN names', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'records' => [
                ['id' => '1', 'name' => 'www.example.com', 'type' => 'A', 'content' => '192.0.2.1', 'ttl' => '600', 'prio' => '0'],
                ['id' => '2', 'name' => 'example.com', 'type' => 'A', 'content' => '192.0.2.2', 'ttl' => '600', 'prio' => '0'],
                ['id' => '3', 'name' => 'deep.sub.example.com', 'type' => 'CNAME', 'content' => 'target.com', 'ttl' => '600', 'prio' => '0'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dnsRecordCollection = $dns->all();

    $first = $dnsRecordCollection->first();
    assert($first instanceof DnsRecord);
    expect($first->name)->toBe('www')
        ->and($first->isRootRecord)->toBeFalse();

    $items = iterator_to_array($dnsRecordCollection);
    expect($items[1]->name)->toBe('')
        ->and($items[1]->isRootRecord)->toBeTrue()
        ->and($items[2]->name)->toBe('deep.sub');
});

test('dns api find normalizes FQDN name', function (): void {
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
    expect($record->name)->toBe('www');
});

test('dns api normalization is case-insensitive and handles trailing dots', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'records' => [
                ['id' => '1', 'name' => 'WWW.Example.COM', 'type' => 'A', 'content' => '192.0.2.1', 'ttl' => '600', 'prio' => '0'],
                ['id' => '2', 'name' => 'Example.COM.', 'type' => 'A', 'content' => '192.0.2.2', 'ttl' => '600', 'prio' => '0'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dnsRecordCollection = $dns->all();
    $items = iterator_to_array($dnsRecordCollection);

    expect($items[0]->name)->toBe('www')
        ->and($items[1]->name)->toBe('')
        ->and($items[1]->isRootRecord)->toBeTrue();
});

test('dns api findByType normalizes FQDN names', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'records' => [
                ['id' => '1', 'name' => 'example.com', 'type' => 'A', 'content' => '192.0.2.1', 'ttl' => '600', 'prio' => '0'],
                ['id' => '2', 'name' => 'www.example.com', 'type' => 'A', 'content' => '192.0.2.2', 'ttl' => '600', 'prio' => '0'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dnsRecordCollection = $dns->findByType('A');

    $first = $dnsRecordCollection->first();
    assert($first instanceof DnsRecord);
    expect($first->name)->toBe('')
        ->and($first->isRootRecord)->toBeTrue();

    $items = iterator_to_array($dnsRecordCollection);
    expect($items[1]->name)->toBe('www');
});
