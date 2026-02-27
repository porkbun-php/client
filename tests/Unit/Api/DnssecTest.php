<?php

declare(strict_types=1);

use Porkbun\Api\Dnssec;
use Porkbun\DTO\DnssecRecord;
use Porkbun\DTO\DnssecRecordCollection;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

test('dnssec api can create record with typed params', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/dns/createDnssecRecord/example.com');

            $body = json_decode((string) $request->getBody(), true);
            expect($body)->toHaveKey('keyTag', '12345')
                ->and($body)->toHaveKey('alg', '13')
                ->and($body)->toHaveKey('digestType', '2')
                ->and($body)->toHaveKey('digest', 'abc123');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $dnssec = new Dnssec(createMockContext($httpClient), 'example.com');

    $dnssec->create(
        keyTag: 12345,
        algorithm: 13,
        digestType: 2,
        digest: 'abc123',
    );
});

test('dnssec api can create record with optional key data', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            $body = json_decode((string) $request->getBody(), true);
            expect($body)->toHaveKey('keyTag', '12345')
                ->and($body)->toHaveKey('alg', '13')
                ->and($body)->toHaveKey('digestType', '2')
                ->and($body)->toHaveKey('digest', 'abc123')
                ->and($body)->toHaveKey('maxSigLife', '86400')
                ->and($body)->toHaveKey('keyDataFlags', '257')
                ->and($body)->toHaveKey('keyDataProtocol', '3')
                ->and($body)->toHaveKey('keyDataAlgo', '13')
                ->and($body)->toHaveKey('keyDataPubKey', 'base64pubkey==');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $dnssec = new Dnssec(createMockContext($httpClient), 'example.com');

    $dnssec->create(
        keyTag: 12345,
        algorithm: 13,
        digestType: 2,
        digest: 'abc123',
        maxSigLife: 86400,
        flags: 257,
        protocol: 3,
        publicKey: 'base64pubkey==',
    );
});

test('dnssec api can get all records', function (): void {
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
    $dnssec = new Dnssec(createMockContext($httpClient), 'example.com');

    $dnssecRecordCollection = $dnssec->all();

    expect($dnssecRecordCollection)->toBeInstanceOf(DnssecRecordCollection::class)
        ->and($dnssecRecordCollection)->toHaveCount(1);

    $record = $dnssecRecordCollection->first();
    assert($record instanceof DnssecRecord);
    expect($record->keyTag)->toBe(2371)
        ->and($record->algorithm)->toBe(13)
        ->and($record->digestType)->toBe(2)
        ->and($record->digest)->toBe('40A829ECBBC0ABBDD8DCB23526BE8FD25A2D6F49E70260BDB101A42AF6F35E07')
        ->and($record->algorithmName)->toBe('ECDSAP256SHA256')
        ->and($record->digestTypeName)->toBe('SHA-256');
});

test('dnssec api can delete record', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/dns/deleteDnssecRecord/example.com/12345');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $dnssec = new Dnssec(createMockContext($httpClient), 'example.com');

    $dnssec->delete('12345');
});
