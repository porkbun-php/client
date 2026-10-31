<?php

declare(strict_types=1);

use Porkbun\Api\GlueRecords;
use Porkbun\DTO\GlueRecord;
use Porkbun\DTO\GlueRecordCollection;
use Porkbun\DTO\OperationResult;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

test('glue records api reads v4/v6 ip objects and normalizes FQDNs to relative', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'hosts' => [
                ['ns1.example.com', ['v4' => ['192.0.2.1', '192.0.2.2'], 'v6' => ['2001:db8::1']]],
                ['ns2.example.com', ['v4' => ['192.0.2.3'], 'v6' => []]],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $glueRecords = new GlueRecords(createMockContext($httpClient), 'example.com');

    $glueRecordCollection = $glueRecords->all();

    $first = $glueRecordCollection->first();

    assert($first instanceof GlueRecord);
    expect($glueRecordCollection)->toBeInstanceOf(GlueRecordCollection::class)
        ->and($glueRecordCollection)->toHaveCount(2)
        ->and($first->host)->toBe('ns1')
        ->and($first->ips)->toBe(['192.0.2.1', '192.0.2.2', '2001:db8::1'])
        ->and($glueRecordCollection->last()?->host)->toBe('ns2')
        ->and($glueRecordCollection->last()?->ips)->toBe(['192.0.2.3']);
});

test('glue records api reads flat ip lists and skips entries without a host', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'hosts' => [
                [],
                ['ns1.example.com', []],
                ['ns2.example.com', ['192.0.2.2', '2001:db8::2']],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $glueRecords = new GlueRecords(createMockContext($httpClient), 'example.com');

    $glueRecordCollection = $glueRecords->all();

    expect($glueRecordCollection)->toHaveCount(2)
        ->and($glueRecordCollection->find('ns1')?->ips)->toBe([])
        ->and($glueRecordCollection->find('ns2')?->ips)->toBe(['192.0.2.2', '2001:db8::2']);
});

test('glue records api can create record', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/domain/createGlue/example.com/ns1');

            $body = json_decode((string) $request->getBody(), true);
            expect($body['ips'])->toBe(['192.0.2.1', '192.0.2.2']);

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $glueRecords = new GlueRecords(createMockContext($httpClient), 'example.com');

    $result = $glueRecords->create('ns1', '192.0.2.1', '192.0.2.2');

    expect($result)->toBeInstanceOf(OperationResult::class);
});

test('glue records api can update record', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/domain/updateGlue/example.com/ns1');

            $body = json_decode((string) $request->getBody(), true);
            expect($body['ips'])->toBe(['192.0.2.3']);

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $glueRecords = new GlueRecords(createMockContext($httpClient), 'example.com');

    $result = $glueRecords->update('ns1', '192.0.2.3');

    expect($result)->toBeInstanceOf(OperationResult::class);
});

test('glue records api can delete record', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/domain/deleteGlue/example.com/ns1');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $glueRecords = new GlueRecords(createMockContext($httpClient), 'example.com');

    $result = $glueRecords->delete('ns1');

    expect($result)->toBeInstanceOf(OperationResult::class);
});

test('glue records api returns empty collection', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'hosts' => []],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $glueRecords = new GlueRecords(createMockContext($httpClient), 'example.com');

    $glueRecordCollection = $glueRecords->all();

    expect($glueRecordCollection)->toBeInstanceOf(GlueRecordCollection::class)
        ->and($glueRecordCollection->isEmpty())->toBeTrue()
        ->and($glueRecordCollection->first())->toBeNull()
        ->and($glueRecordCollection->last())->toBeNull();
});
