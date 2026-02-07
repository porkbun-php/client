<?php

declare(strict_types=1);

use Porkbun\DTO\DnsRecord;
use Porkbun\DTO\DnsRecordCollection;

test('firstOfType returns correct record when first match is not at index 0', function (): void {
    // Create records where the first A record is NOT at index 0
    $records = [
        DnsRecord::fromArray(['id' => '1', 'name' => '', 'type' => 'MX', 'content' => 'mail.example.com', 'prio' => '10']),
        DnsRecord::fromArray(['id' => '2', 'name' => '', 'type' => 'TXT', 'content' => 'v=spf1 include:_spf.google.com ~all']),
        DnsRecord::fromArray(['id' => '3', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1']),
        DnsRecord::fromArray(['id' => '4', 'name' => 'api', 'type' => 'A', 'content' => '192.0.2.2']),
    ];

    $collection = new DnsRecordCollection($records);

    $firstA = $collection->firstOfType('A');

    // This was the bug: firstOfType would return null because array_filter preserves keys
    expect($firstA)->toBeInstanceOf(DnsRecord::class);

    /** @var DnsRecord $firstA */
    expect($firstA->id)->toBe(3);
    expect($firstA->content)->toBe('192.0.2.1');
});

test('getRecordsByType returns 0-indexed array', function (): void {
    $records = [
        DnsRecord::fromArray(['id' => '1', 'name' => '', 'type' => 'MX', 'content' => 'mail.example.com', 'prio' => '10']),
        DnsRecord::fromArray(['id' => '2', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1']),
        DnsRecord::fromArray(['id' => '3', 'name' => '', 'type' => 'TXT', 'content' => 'some text']),
        DnsRecord::fromArray(['id' => '4', 'name' => 'api', 'type' => 'A', 'content' => '192.0.2.2']),
    ];

    $collection = new DnsRecordCollection($records);

    $aRecords = $collection->getRecordsByType('A');

    // Should be 0-indexed, not preserve original keys
    expect(array_keys($aRecords))->toBe([0, 1])
        ->and($aRecords[0]->id)->toBe(2)
        ->and($aRecords[1]->id)->toBe(4);
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

    $record = $collection->getRecordById(42);

    expect($record)->toBeInstanceOf(DnsRecord::class);

    /** @var DnsRecord $record */
    expect($record->name)->toBe('api');

    expect($collection->getRecordById(999))->toBeNull();
});
