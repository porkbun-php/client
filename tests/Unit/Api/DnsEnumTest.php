<?php

declare(strict_types=1);

use Porkbun\Api\Dns;
use Porkbun\Enum\DnsRecordType;

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

    $createResult = $dns->create('www', DnsRecordType::A, '192.0.2.1');

    expect($createResult->id)->toBe(12345);
});

test('updateByType accepts DnsRecordType enum', function (): void {
    $mock = createMockHttpClient([
        ['status' => 'SUCCESS'],
    ]);

    $httpClient = createHttpClient($mock, 'pk1_test', 'sk1_test');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dns->updateByType(DnsRecordType::A, '192.0.2.2', 'www');

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
