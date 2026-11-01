<?php

declare(strict_types=1);

use Porkbun\DTO\TransferRecord;

test('it creates a transfer record from the api response', function (): void {
    $record = TransferRecord::fromArray([
        'domain' => 'example.com',
        'status' => 'DONE',
        'statusDescription' => 'Transfer completed.',
        'transferDate' => '2026-11-01 11:04:28',
        'orderId' => '9915398',
    ]);

    expect($record->domain)->toBe('example.com')
        ->and($record->status)->toBe('DONE')
        ->and($record->statusDescription)->toBe('Transfer completed.')
        ->and($record->orderId)->toBe(9915398);
});

test('it reads a transfer date without a zone as utc', function (): void {
    $defaultTimezone = date_default_timezone_get();
    date_default_timezone_set('America/New_York');

    try {
        $record = TransferRecord::fromArray(['transferDate' => '2026-11-01 11:04:28']);
    } finally {
        date_default_timezone_set($defaultTimezone);
    }

    expect($record->transferDate?->getTimestamp())->toBe(1793531068);
});

test('it reads an iso 8601 transfer date', function (): void {
    $record = TransferRecord::fromArray(['transferDate' => '2026-01-01T00:00:00Z']);

    expect($record->transferDate?->format(DATE_ATOM))->toBe('2026-01-01T00:00:00+00:00');
});

test('it keeps a status the spec does not list', function (): void {
    $record = TransferRecord::fromArray(['domain' => 'example.com', 'status' => 'PENDINGDNS']);

    expect($record->status)->toBe('PENDINGDNS');
});

test('it leaves an invalid transfer date null', function (): void {
    foreach (['not a date', '', false, null] as $transferDate) {
        expect(TransferRecord::fromArray(['transferDate' => $transferDate])->transferDate)->toBeNull();
    }
});

test('transfer record round trips through toArray', function (): void {
    $record = TransferRecord::fromArray([
        'domain' => 'example.com',
        'status' => 'PENDINGTRANSFER',
        'statusDescription' => 'Waiting for the losing registrar.',
        'transferDate' => '2026-11-01 11:04:28',
        'orderId' => 9915398,
    ]);

    expect(TransferRecord::fromArray($record->toArray()))->toEqual($record)
        ->and($record->jsonSerialize())->toBe($record->toArray());
});
