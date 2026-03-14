<?php

declare(strict_types=1);

use Porkbun\DTO\AutoRenewResult;

test('fromArray creates result from array data', function (): void {
    $result = AutoRenewResult::fromArray([
        'domain' => 'example.com',
        'success' => true,
        'message' => 'Auto renew enabled',
    ]);

    expect($result->domain)->toBe('example.com')
        ->and($result->success)->toBeTrue()
        ->and($result->message)->toBe('Auto renew enabled')
        ->and($result->isFailure)->toBeFalse();
});

test('fromArray handles defaults', function (): void {
    $result = AutoRenewResult::fromArray([]);

    expect($result->domain)->toBe('')
        ->and($result->success)->toBeFalse()
        ->and($result->message)->toBeNull()
        ->and($result->isFailure)->toBeTrue();
});

test('toArray serializes all fields', function (): void {
    $result = new AutoRenewResult(
        domain: 'example.com',
        success: true,
        message: 'Done',
    );

    expect($result->toArray())->toBe([
        'domain' => 'example.com',
        'success' => true,
        'message' => 'Done',
    ]);
});

test('toArray omits null message', function (): void {
    $result = new AutoRenewResult(
        domain: 'example.com',
        success: true,
    );

    $array = $result->toArray();

    expect($array)->toBe([
        'domain' => 'example.com',
        'success' => true,
    ])->and($array)->not->toHaveKey('message');
});

test('jsonSerialize delegates to toArray', function (): void {
    $result = new AutoRenewResult(
        domain: 'example.com',
        success: false,
        message: 'Failed',
    );

    expect($result->jsonSerialize())->toBe($result->toArray());
});

test('round-trip through fromArray and toArray', function (): void {
    $original = new AutoRenewResult(
        domain: 'example.com',
        success: true,
        message: 'Auto renew status updated.',
    );

    $roundTripped = AutoRenewResult::fromArray($original->toArray());

    expect($roundTripped->domain)->toBe($original->domain)
        ->and($roundTripped->success)->toBe($original->success)
        ->and($roundTripped->message)->toBe($original->message);
});

test('isFailure is inverse of success', function (): void {
    $success = new AutoRenewResult(domain: 'a.com', success: true);
    $failure = new AutoRenewResult(domain: 'b.com', success: false);

    expect($success->isFailure)->toBeFalse()
        ->and($failure->isFailure)->toBeTrue();
});
