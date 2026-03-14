<?php

declare(strict_types=1);

use Porkbun\DTO\DnsRecord;
use Porkbun\DTO\DnsRecordCollection;
use Porkbun\Enum\DnsRecordType;
use Porkbun\Exception\InvalidArgumentException;

test('items returns all records', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray(['records' => [
        ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1'],
        ['id' => '2', 'name' => 'api', 'type' => 'A', 'content' => '192.0.2.2'],
    ]]);

    expect($dnsRecordCollection->items())->toHaveCount(2);
});

test('byName filters by name', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray(['records' => [
        ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1'],
        ['id' => '2', 'name' => 'www', 'type' => 'AAAA', 'content' => '2001:db8::1'],
        ['id' => '3', 'name' => 'api', 'type' => 'A', 'content' => '192.0.2.2'],
    ]]);

    $records = $dnsRecordCollection->byName('www');

    expect($records)->toBeInstanceOf(DnsRecordCollection::class)
        ->and($records)->toHaveCount(2);
});

test('rootRecords returns only root records', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray(['records' => [
        ['id' => '1', 'name' => '', 'type' => 'A', 'content' => '192.0.2.1'],
        ['id' => '2', 'name' => '@', 'type' => 'MX', 'content' => 'mail.example.com', 'prio' => '10'],
        ['id' => '3', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.2'],
    ]]);

    $records = $dnsRecordCollection->rootRecords;

    expect($records)->toHaveCount(2)
        ->and($records[0]->id)->toBe(1)
        ->and($records[1]->id)->toBe(2);
});

test('byTypeAndName filters by both', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray(['records' => [
        ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1'],
        ['id' => '2', 'name' => 'www', 'type' => 'AAAA', 'content' => '2001:db8::1'],
        ['id' => '3', 'name' => 'api', 'type' => 'A', 'content' => '192.0.2.2'],
    ]]);

    $records = $dnsRecordCollection->byTypeAndName('A', 'www');

    $first = $records->first();

    assert($first instanceof DnsRecord);
    expect($records)->toBeInstanceOf(DnsRecordCollection::class)
        ->and($records)->toHaveCount(1)
        ->and($first->id)->toBe(1);
});

test('filter applies custom callback', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray(['records' => [
        ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1', 'ttl' => '600'],
        ['id' => '2', 'name' => 'api', 'type' => 'A', 'content' => '192.0.2.2', 'ttl' => '3600'],
        ['id' => '3', 'name' => 'mail', 'type' => 'A', 'content' => '192.0.2.3', 'ttl' => '7200'],
    ]]);

    $filtered = $dnsRecordCollection->filter(fn (DnsRecord $dnsRecord): bool => $dnsRecord->ttl >= 3600);

    expect($filtered)->toBeInstanceOf(DnsRecordCollection::class)
        ->and($filtered)->toHaveCount(2);
});

test('toArray serializes all records without cloudflare', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray(['records' => [
        ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1'],
    ]]);

    /** @var list<array<string, mixed>> $array */
    $array = $dnsRecordCollection->toArray();

    expect($array)->toBeArray()
        ->and($array[0]['id'])->toBe(1)
        ->and($array[0]['type'])->toBe('A');
});

test('toArray returns structured format when cloudflare is set', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray([
        'records' => [
            ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1'],
        ],
        'cloudflare' => 'enabled',
    ]);

    $array = $dnsRecordCollection->toArray();

    expect($array)->toBeArray()
        ->and($array)->toHaveKey('records')
        ->and($array)->toHaveKey('cloudflare')
        ->and($array['cloudflare'])->toBe('enabled')
        ->and($array['records'][0]['id'])->toBe(1)
        ->and($array['records'][0]['type'])->toBe('A');
});

test('toArray round-trips records with fromArray preserving cloudflare', function (): void {
    $original = DnsRecordCollection::fromArray([
        'records' => [
            ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1'],
            ['id' => '2', 'name' => 'api', 'type' => 'A', 'content' => '192.0.2.2'],
        ],
        'cloudflare' => 'enabled',
    ]);

    $roundTripped = DnsRecordCollection::fromArray($original->toArray());

    expect($roundTripped->cloudflare)->toBe('enabled')
        ->and($roundTripped->isCloudflareEnabled)->toBeTrue()
        ->and($roundTripped)->toHaveCount(2);
});

test('jsonSerialize returns same as toArray', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray([
        'records' => [
            ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1'],
        ],
        'cloudflare' => 'enabled',
    ]);

    $array = $dnsRecordCollection->toArray();

    expect($dnsRecordCollection->jsonSerialize())->toBe($array)
        ->and($array['records'][0]['id'])->toBe(1);
});

test('has checks for record existence by id', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray(['records' => [
        ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1'],
        ['id' => '42', 'name' => 'api', 'type' => 'A', 'content' => '192.0.2.2'],
    ]]);

    expect($dnsRecordCollection->has(1))->toBeTrue()
        ->and($dnsRecordCollection->has(42))->toBeTrue()
        ->and($dnsRecordCollection->has(999))->toBeFalse();
});

test('cloudflare properties from fromArray', function (): void {
    $withCloudflare = DnsRecordCollection::fromArray([
        'records' => [['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1']],
        'cloudflare' => 'enabled',
    ]);

    expect($withCloudflare->cloudflare)->toBe('enabled')
        ->and($withCloudflare->isCloudflareEnabled)->toBeTrue();

    $withoutCloudflare = DnsRecordCollection::fromArray([
        'records' => [['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1']],
    ]);

    expect($withoutCloudflare->cloudflare)->toBeNull()
        ->and($withoutCloudflare->isCloudflareEnabled)->toBeFalse();

    $disabledCloudflare = DnsRecordCollection::fromArray([
        'records' => [['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1']],
        'cloudflare' => 'disabled',
    ]);

    expect($disabledCloudflare->cloudflare)->toBe('disabled')
        ->and($disabledCloudflare->isCloudflareEnabled)->toBeFalse();
});

test('cloudflare defaults via constructor', function (): void {
    $collection = new DnsRecordCollection();

    expect($collection->cloudflare)->toBeNull()
        ->and($collection->isCloudflareEnabled)->toBeFalse();
});

test('byType accepts DnsRecordType enum', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray(['records' => [
        ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1'],
        ['id' => '2', 'name' => 'mail', 'type' => 'MX', 'content' => 'mail.example.com', 'prio' => '10'],
        ['id' => '3', 'name' => 'api', 'type' => 'A', 'content' => '192.0.2.2'],
    ]]);

    $aRecords = $dnsRecordCollection->byType(DnsRecordType::A);

    expect($aRecords)->toHaveCount(2)
        ->and($aRecords->first()?->id)->toBe(1);
});

test('byTypeAndName accepts DnsRecordType enum', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray(['records' => [
        ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1'],
        ['id' => '2', 'name' => 'www', 'type' => 'AAAA', 'content' => '2001:db8::1'],
        ['id' => '3', 'name' => 'api', 'type' => 'A', 'content' => '192.0.2.2'],
    ]]);

    $records = $dnsRecordCollection->byTypeAndName(DnsRecordType::A, 'www');

    expect($records)->toHaveCount(1)
        ->and($records->first()?->id)->toBe(1);
});

test('byType throws InvalidArgumentException for invalid string', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray(['records' => [
        ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1'],
    ]]);

    expect(fn (): DnsRecordCollection => $dnsRecordCollection->byType('INVALID'))
        ->toThrow(InvalidArgumentException::class);
});

test('byTypeAndName throws InvalidArgumentException for invalid string', function (): void {
    $dnsRecordCollection = DnsRecordCollection::fromArray(['records' => [
        ['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1'],
    ]]);

    expect(fn (): DnsRecordCollection => $dnsRecordCollection->byTypeAndName('INVALID', 'www'))
        ->toThrow(InvalidArgumentException::class);
});
