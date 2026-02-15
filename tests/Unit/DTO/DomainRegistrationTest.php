<?php

declare(strict_types=1);

use Porkbun\DTO\DomainRegistration;

test('it creates domain registration from array', function (): void {
    $data = [
        'domain' => 'example.com',
        'cost' => 1108,
        'orderId' => 123456789,
        'balance' => 10000,
        'limits' => [
            'attempts' => [
                'TTL' => 10,
                'limit' => 1,
                'used' => 1,
            ],
            'success' => [
                'TTL' => 86400,
                'limit' => 10,
                'used' => 3,
            ],
        ],
    ];

    $domainRegistration = DomainRegistration::fromArray($data);

    expect($domainRegistration->domain)->toBe('example.com')
        ->and($domainRegistration->cost)->toBe(1108)
        ->and($domainRegistration->orderId)->toBe(123456789)
        ->and($domainRegistration->balance)->toBe(10000)
        ->and($domainRegistration->hasLimits())->toBeTrue();
});

test('it handles missing limits', function (): void {
    $data = [
        'domain' => 'example.com',
        'cost' => 500,
        'orderId' => 1,
        'balance' => 100,
    ];

    $domainRegistration = DomainRegistration::fromArray($data);

    expect($domainRegistration->hasLimits())->toBeFalse()
        ->and($domainRegistration->getAttemptsLimit())->toBeNull()
        ->and($domainRegistration->getSuccessLimit())->toBeNull()
        ->and($domainRegistration->getRemainingAttempts())->toBeNull()
        ->and($domainRegistration->getRemainingSuccessfulRegistrations())->toBeNull();
});

test('getCostInDollars converts cents to dollars', function (): void {
    $domainRegistration = DomainRegistration::fromArray([
        'domain' => 'example.com',
        'cost' => 1108,
        'orderId' => 1,
        'balance' => 0,
    ]);

    expect($domainRegistration->getCostInDollars())->toBe(11.08);
});

test('getBalanceInDollars converts cents to dollars', function (): void {
    $domainRegistration = DomainRegistration::fromArray([
        'domain' => 'example.com',
        'cost' => 0,
        'orderId' => 1,
        'balance' => 10000,
    ]);

    expect($domainRegistration->getBalanceInDollars())->toBe(100.00);
});

test('getRemainingAttempts calculates correctly', function (): void {
    $domainRegistration = DomainRegistration::fromArray([
        'domain' => 'example.com',
        'cost' => 0,
        'orderId' => 1,
        'balance' => 0,
        'limits' => [
            'attempts' => ['limit' => 5, 'used' => 3],
        ],
    ]);

    expect($domainRegistration->getRemainingAttempts())->toBe(2);
});

test('getRemainingSuccessfulRegistrations calculates correctly', function (): void {
    $domainRegistration = DomainRegistration::fromArray([
        'domain' => 'example.com',
        'cost' => 0,
        'orderId' => 1,
        'balance' => 0,
        'limits' => [
            'success' => ['limit' => 10, 'used' => 7],
        ],
    ]);

    expect($domainRegistration->getRemainingSuccessfulRegistrations())->toBe(3);
});

test('toArray serializes correctly', function (): void {
    $data = [
        'domain' => 'example.com',
        'cost' => 1108,
        'orderId' => 123,
        'balance' => 5000,
        'limits' => ['attempts' => ['limit' => 1, 'used' => 1]],
    ];

    $domainRegistration = DomainRegistration::fromArray($data);

    expect($domainRegistration->toArray())->toBe($data);
});

test('toArray omits null limits', function (): void {
    $domainRegistration = DomainRegistration::fromArray([
        'domain' => 'example.com',
        'cost' => 500,
        'orderId' => 1,
        'balance' => 100,
    ]);

    $array = $domainRegistration->toArray();

    expect($array)->not->toHaveKey('limits');
});

test('jsonSerialize returns toArray', function (): void {
    $domainRegistration = DomainRegistration::fromArray([
        'domain' => 'example.com',
        'cost' => 500,
        'orderId' => 1,
        'balance' => 100,
    ]);

    expect($domainRegistration->jsonSerialize())->toBe($domainRegistration->toArray());
});
