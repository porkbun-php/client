<?php

declare(strict_types=1);

use Porkbun\DTO\DnsRecord;
use Porkbun\DTO\DnsRecordCollection;

test('byType returns a filtered collection', function (): void {
    $records = [
        DnsRecord::fromArray(['id' => '1', 'name' => '', 'type' => 'MX', 'content' => 'mail.example.com', 'prio' => '10']),
        DnsRecord::fromArray(['id' => '2', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1']),
        DnsRecord::fromArray(['id' => '3', 'name' => '', 'type' => 'TXT', 'content' => 'some text']),
        DnsRecord::fromArray(['id' => '4', 'name' => 'api', 'type' => 'A', 'content' => '192.0.2.2']),
    ];

    $collection = new DnsRecordCollection($records);

    $aRecords = $collection->byType('A');

    $first = $aRecords->first();
    $last = $aRecords->last();

    assert($first instanceof DnsRecord);
    assert($last instanceof DnsRecord);
    expect($aRecords)->toBeInstanceOf(DnsRecordCollection::class)
        ->and($aRecords)->toHaveCount(2)
        ->and($first->id)->toBe(2)
        ->and($last->id)->toBe(4);
});

test('collection is iterable', function (): void {
    $records = [
        DnsRecord::fromArray(['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1']),
        DnsRecord::fromArray(['id' => '2', 'name' => 'api', 'type' => 'A', 'content' => '192.0.2.2']),
    ];

    $collection = new DnsRecordCollection($records);

    $ids = [];
    foreach ($collection as $record) {
        $ids[] = $record->id;
    }

    expect($ids)->toBe([1, 2]);
});

test('collection is countable', function (): void {
    $records = [
        DnsRecord::fromArray(['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1']),
        DnsRecord::fromArray(['id' => '2', 'name' => 'api', 'type' => 'A', 'content' => '192.0.2.2']),
    ];

    $collection = new DnsRecordCollection($records);

    expect(count($collection))->toBe(2);
});

test('collection can find record by id', function (): void {
    $records = [
        DnsRecord::fromArray(['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1']),
        DnsRecord::fromArray(['id' => '42', 'name' => 'api', 'type' => 'A', 'content' => '192.0.2.2']),
    ];

    $collection = new DnsRecordCollection($records);

    $record = $collection->find(42);

    expect($record)->toBeInstanceOf(DnsRecord::class);

    /** @var DnsRecord $record */
    expect($record->name)->toBe('api');

    expect($collection->find(999))->toBeNull();
});

test('last returns last record', function (): void {
    $records = [
        DnsRecord::fromArray(['id' => '1', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1']),
        DnsRecord::fromArray(['id' => '2', 'name' => 'api', 'type' => 'A', 'content' => '192.0.2.2']),
        DnsRecord::fromArray(['id' => '3', 'name' => 'mail', 'type' => 'MX', 'content' => 'mail.example.com', 'prio' => '10']),
    ];

    $collection = new DnsRecordCollection($records);

    $last = $collection->last();

    expect($last)->toBeInstanceOf(DnsRecord::class);

    /** @var DnsRecord $last */
    expect($last->id)->toBe(3)
        ->and($last->name)->toBe('mail');
});

test('last returns null for empty collection', function (): void {
    $collection = new DnsRecordCollection();

    expect($collection->last())->toBeNull();
});
