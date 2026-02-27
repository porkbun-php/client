<?php

declare(strict_types=1);

use Porkbun\DTO\DnsRecord;
use Porkbun\DTO\DnsRecordCollection;

test('all returns all records', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray([
        ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1'],
        ['id' => '2', 'name' => 'api', 'type' => 'A', 'content' => '192.0.2.2'],
    ]);

    expect($dnsRecordCollection->all())->toHaveCount(2);
});

test('byName filters by name', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray([
        ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1'],
        ['id' => '2', 'name' => 'www', 'type' => 'AAAA', 'content' => '2001:db8::1'],
        ['id' => '3', 'name' => 'api', 'type' => 'A', 'content' => '192.0.2.2'],
    ]);

    $records = $dnsRecordCollection->byName('www');

    expect($records)->toHaveCount(2)
        ->and(array_keys($records))->toBe([0, 1]);
});

test('rootRecords returns only root records', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray([
        ['id' => '1', 'name' => '', 'type' => 'A', 'content' => '192.0.2.1'],
        ['id' => '2', 'name' => '@', 'type' => 'MX', 'content' => 'mail.example.com', 'prio' => '10'],
        ['id' => '3', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.2'],
    ]);

    $records = $dnsRecordCollection->rootRecords;

    expect($records)->toHaveCount(2)
        ->and($records[0]->id)->toBe(1)
        ->and($records[1]->id)->toBe(2);
});

test('byTypeAndName filters by both', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray([
        ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1'],
        ['id' => '2', 'name' => 'www', 'type' => 'AAAA', 'content' => '2001:db8::1'],
        ['id' => '3', 'name' => 'api', 'type' => 'A', 'content' => '192.0.2.2'],
    ]);

    $records = $dnsRecordCollection->byTypeAndName('A', 'www');

    expect($records)->toHaveCount(1)
        ->and($records[0]->id)->toBe(1);
});

test('filter applies custom callback', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray([
        ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1', 'ttl' => '600'],
        ['id' => '2', 'name' => 'api', 'type' => 'A', 'content' => '192.0.2.2', 'ttl' => '3600'],
        ['id' => '3', 'name' => 'mail', 'type' => 'A', 'content' => '192.0.2.3', 'ttl' => '7200'],
    ]);

    $filtered = $dnsRecordCollection->filter(fn (DnsRecord $dnsRecord): bool => $dnsRecord->ttl >= 3600);

    expect($filtered)->toHaveCount(2)
        ->and(array_keys($filtered))->toBe([0, 1]);
});

test('toArray serializes all records', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray([
        ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1'],
    ]);

    $array = $dnsRecordCollection->toArray();

    expect($array)->toBeArray()
        ->and($array[0]['id'])->toBe(1)
        ->and($array[0]['type'])->toBe('A');
});

test('jsonSerialize returns same as toArray', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray([
        ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1'],
    ]);

    expect($dnsRecordCollection->jsonSerialize())->toBe($dnsRecordCollection->toArray());
});
