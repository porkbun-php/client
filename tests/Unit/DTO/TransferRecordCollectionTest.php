<?php

declare(strict_types=1);

use Porkbun\DTO\TransferRecord;
use Porkbun\DTO\TransferRecordCollection;

function transferRecords(): TransferRecordCollection
{
    return TransferRecordCollection::fromArray([
        ['domain' => 'example.com', 'status' => 'PENDINGTRANSFER', 'statusDescription' => '', 'orderId' => 1],
        ['domain' => 'example.org', 'status' => 'PENDINGAUTH', 'statusDescription' => '', 'orderId' => 2],
    ]);
}

test('transfer record collection gives access to its items', function (): void {
    $collection = transferRecords();

    expect($collection)->toHaveCount(2)
        ->and($collection->items())->toHaveCount(2)
        ->and($collection->first()?->domain)->toBe('example.com')
        ->and($collection->last()?->domain)->toBe('example.org')
        ->and($collection->isEmpty())->toBeFalse()
        ->and($collection->isNotEmpty())->toBeTrue();
});

test('transfer record collection finds a domain ignoring case', function (): void {
    $collection = transferRecords();

    expect($collection->find('EXAMPLE.org')?->orderId)->toBe(2)
        ->and($collection->has('example.com'))->toBeTrue()
        ->and($collection->has('example.net'))->toBeFalse();
});

test('transfer record collection filters', function (): void {
    $pending = transferRecords()->filter(fn (TransferRecord $record): bool => $record->status === 'PENDINGAUTH');

    expect($pending)->toHaveCount(1)
        ->and($pending->first()?->domain)->toBe('example.org');
});

test('transfer record collection skips items that are not objects', function (): void {
    $collection = TransferRecordCollection::fromArray([['domain' => 'example.com'], 'example.org', null]);

    expect($collection)->toHaveCount(1);
});

test('empty transfer record collection', function (): void {
    $collection = TransferRecordCollection::fromArray([]);

    expect($collection->isEmpty())->toBeTrue()
        ->and($collection->first())->toBeNull()
        ->and($collection->last())->toBeNull()
        ->and($collection->toArray())->toBe([]);
});

test('transfer record collection serializes its items', function (): void {
    $collection = transferRecords();

    expect($collection->toArray()[1]['domain'])->toBe('example.org')
        ->and($collection->jsonSerialize())->toBe($collection->toArray());
});
