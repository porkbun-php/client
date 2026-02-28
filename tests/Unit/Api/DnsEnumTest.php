<?php

declare(strict_types=1);

use Porkbun\Api\Dns;
use Porkbun\DTO\CreateResult;
use Porkbun\Enum\DnsRecordType;
use Porkbun\Exception\InvalidArgumentException;

test('findByType accepts DnsRecordType enum', function (): void {
    $mock = createMockHttpClient([
        ['status' => 'SUCCESS', 'records' => [
            ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1'],
        ]],
    ]);

    $httpClient = createHttpClient($mock, 'pk1_test', 'sk1_test');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dnsRecordCollection = $dns->findByType(DnsRecordType::A);
    $first = $dnsRecordCollection->first();

    expect($dnsRecordCollection)->toHaveCount(1)
        ->and($first)->not->toBeNull()
        ->and($first?->content)->toBe('192.0.2.1');
});

test('create accepts DnsRecordType enum', function (): void {
    $mock = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 12345],
    ]);

    $httpClient = createHttpClient($mock, 'pk1_test', 'sk1_test');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $createResult = $dns->create(DnsRecordType::A, 'www', '192.0.2.1');

    expect($createResult->id)->toBe(12345);
});

test('updateByType accepts DnsRecordType enum', function (): void {
    $mock = createMockHttpClient([
        ['status' => 'SUCCESS'],
    ]);

    $httpClient = createHttpClient($mock, 'pk1_test', 'sk1_test');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dns->updateByType(DnsRecordType::A, 'www', '192.0.2.2');

    expect(true)->toBeTrue();
});

test('deleteByType accepts DnsRecordType enum', function (): void {
    $mock = createMockHttpClient([
        ['status' => 'SUCCESS'],
    ]);

    $httpClient = createHttpClient($mock, 'pk1_test', 'sk1_test');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dns->deleteByType(DnsRecordType::A, 'old');

    expect(true)->toBeTrue();
});

test('create accepts lowercase string type', function (): void {
    $mock = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 100],
    ]);

    $httpClient = createHttpClient($mock, 'pk1_test', 'sk1_test');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $createResult = $dns->create('a', 'www', '192.0.2.1');

    expect($createResult->id)->toBe(100);
});

test('findByType accepts lowercase string type', function (): void {
    $mock = createMockHttpClient([
        ['status' => 'SUCCESS', 'records' => []],
    ]);

    $httpClient = createHttpClient($mock, 'pk1_test', 'sk1_test');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $records = $dns->findByType('aaaa');

    expect($records)->toHaveCount(0);
});

test('create rejects invalid string type', function (): void {
    $mock = createMockHttpClient([]);

    $httpClient = createHttpClient($mock, 'pk1_test', 'sk1_test');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    expect(fn (): CreateResult => $dns->create('INVALID', 'www', '192.0.2.1'))
        ->toThrow(InvalidArgumentException::class, 'Invalid DNS record type: INVALID');
});
