<?php

declare(strict_types=1);

use Porkbun\Api\GlueRecords;
use Porkbun\DTO\GlueRecord;
use Porkbun\DTO\GlueRecordCollection;
use Porkbun\DTO\OperationResult;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

test('glue records api can get all records and normalizes FQDNs to relative', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'glue' => [
                ['host' => 'ns1.example.com', 'ip' => ['192.0.2.1', '192.0.2.2']],
                ['host' => 'ns2.example.com', 'ip' => ['192.0.2.3']],
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
        ->and($first)->toBeInstanceOf(GlueRecord::class)
        ->and($first->host)->toBe('ns1')
        ->and($glueRecordCollection->last()?->host)->toBe('ns2');
});

test('glue records api can create record', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/domain/createGlue/example.com/ns1');

            $body = json_decode((string) $request->getBody(), true);
            expect($body)->toHaveKey('ip1', '192.0.2.1')
                ->and($body)->toHaveKey('ip2', '192.0.2.2');

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
        ['status' => 'SUCCESS', 'glue' => []],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $glueRecords = new GlueRecords(createMockContext($httpClient), 'example.com');

    $glueRecordCollection = $glueRecords->all();

    expect($glueRecordCollection)->toBeInstanceOf(GlueRecordCollection::class)
        ->and($glueRecordCollection->isEmpty())->toBeTrue()
        ->and($glueRecordCollection->first())->toBeNull()
        ->and($glueRecordCollection->last())->toBeNull();
});
