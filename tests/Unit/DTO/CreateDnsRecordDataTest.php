<?php

declare(strict_types=1);

use Porkbun\DTO\CreateDnsRecordData;

test('it creates data from array', function (): void {
    $createDnsRecordData = CreateDnsRecordData::fromArray([
        'id' => '123456',
        'createdAt' => '2024-01-15 10:30:00',
        'validationWarnings' => ['TTL too low'],
    ]);

    expect($createDnsRecordData->id)->toBe(123456)
        ->and($createDnsRecordData->createdAt)->toBe('2024-01-15 10:30:00')
        ->and($createDnsRecordData->validationWarnings)->toBe(['TTL too low']);
});

test('it handles missing optional fields', function (): void {
    $createDnsRecordData = CreateDnsRecordData::fromArray([
        'id' => '789',
    ]);

    expect($createDnsRecordData->id)->toBe(789)
        ->and($createDnsRecordData->createdAt)->toBeNull()
        ->and($createDnsRecordData->validationWarnings)->toBeNull();
});

test('it handles missing id', function (): void {
    $createDnsRecordData = CreateDnsRecordData::fromArray([]);

    expect($createDnsRecordData->id)->toBe(0);
});

test('hasValidId checks for positive id', function (): void {
    $createDnsRecordData = CreateDnsRecordData::fromArray(['id' => '123']);
    $zero = CreateDnsRecordData::fromArray(['id' => '0']);
    $missing = CreateDnsRecordData::fromArray([]);

    expect($createDnsRecordData->hasValidId())->toBeTrue()
        ->and($zero->hasValidId())->toBeFalse()
        ->and($missing->hasValidId())->toBeFalse();
});

test('hasValidationWarnings checks for non-empty array', function (): void {
    $createDnsRecordData = CreateDnsRecordData::fromArray([
        'id' => '123',
        'validationWarnings' => ['Warning 1'],
    ]);
    $emptyWarnings = CreateDnsRecordData::fromArray([
        'id' => '123',
        'validationWarnings' => [],
    ]);
    $nullWarnings = CreateDnsRecordData::fromArray([
        'id' => '123',
    ]);

    expect($createDnsRecordData->hasValidationWarnings())->toBeTrue()
        ->and($emptyWarnings->hasValidationWarnings())->toBeFalse()
        ->and($nullWarnings->hasValidationWarnings())->toBeFalse();
});

test('toArray serializes all fields', function (): void {
    $data = new CreateDnsRecordData(
        id: 456,
        createdAt: '2024-01-15 10:30:00',
        validationWarnings: ['Warning'],
    );

    expect($data->toArray())->toBe([
        'id' => 456,
        'createdAt' => '2024-01-15 10:30:00',
        'validationWarnings' => ['Warning'],
    ]);
});

test('toArray omits null optional fields', function (): void {
    $data = new CreateDnsRecordData(id: 789);

    expect($data->toArray())->toBe(['id' => 789]);
});

test('jsonSerialize returns toArray', function (): void {
    $data = new CreateDnsRecordData(id: 123);

    expect($data->jsonSerialize())->toBe($data->toArray());
});
