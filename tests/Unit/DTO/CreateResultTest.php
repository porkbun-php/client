<?php

declare(strict_types=1);

use Porkbun\DTO\CreateResult;

test('it creates data from array', function (): void {
    $createResult = CreateResult::fromArray([
        'id' => '123456',
        'createdAt' => '2024-01-15 10:30:00',
        'validationWarnings' => ['TTL too low'],
    ]);

    expect($createResult->id)->toBe(123456)
        ->and($createResult->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($createResult->validationWarnings)->toBe(['TTL too low']);
});

test('it handles missing optional fields', function (): void {
    $createResult = CreateResult::fromArray([
        'id' => '789',
    ]);

    expect($createResult->id)->toBe(789)
        ->and($createResult->createdAt)->toBeNull()
        ->and($createResult->validationWarnings)->toBeNull();
});

test('it handles missing id', function (): void {
    $createResult = CreateResult::fromArray([]);

    expect($createResult->id)->toBe(0);
});

test('hasValidId checks for positive id', function (): void {
    $createResult = CreateResult::fromArray(['id' => '123']);
    $zero = CreateResult::fromArray(['id' => '0']);
    $missing = CreateResult::fromArray([]);

    expect($createResult->hasValidId)->toBeTrue()
        ->and($zero->hasValidId)->toBeFalse()
        ->and($missing->hasValidId)->toBeFalse();
});

test('hasValidationWarnings checks for non-empty array', function (): void {
    $createResult = CreateResult::fromArray([
        'id' => '123',
        'validationWarnings' => ['Warning 1'],
    ]);
    $emptyWarnings = CreateResult::fromArray([
        'id' => '123',
        'validationWarnings' => [],
    ]);
    $nullWarnings = CreateResult::fromArray([
        'id' => '123',
    ]);

    expect($createResult->hasValidationWarnings)->toBeTrue()
        ->and($emptyWarnings->hasValidationWarnings)->toBeFalse()
        ->and($nullWarnings->hasValidationWarnings)->toBeFalse();
});

test('toArray serializes all fields', function (): void {
    $createdAt = new DateTimeImmutable('2024-01-15 10:30:00');
    $data = new CreateResult(
        id: 456,
        createdAt: $createdAt,
        validationWarnings: ['Warning'],
    );

    $array = $data->toArray();

    expect($array['id'])->toBe(456)
        ->and($array['createdAt'])->toBe($createdAt->format('c'))
        ->and($array['validationWarnings'])->toBe(['Warning']);
});

test('toArray omits null optional fields', function (): void {
    $data = new CreateResult(id: 789);

    expect($data->toArray())->toBe(['id' => 789]);
});

test('jsonSerialize returns toArray', function (): void {
    $data = new CreateResult(id: 123);

    expect($data->jsonSerialize())->toBe($data->toArray());
});

test('round-trip through fromArray and toArray', function (): void {
    $original = CreateResult::fromArray([
        'id' => '456',
        'createdAt' => '2024-01-15 10:30:00',
    ]);

    $roundTripped = CreateResult::fromArray($original->toArray());

    expect($roundTripped->id)->toBe(456)
        ->and($roundTripped->createdAt)->toBeInstanceOf(DateTimeImmutable::class);
});
