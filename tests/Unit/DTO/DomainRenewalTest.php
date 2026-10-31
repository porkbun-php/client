<?php

declare(strict_types=1);

use Porkbun\DTO\DomainRenewal;

test('it creates domain renewal from array', function (): void {
    $data = [
        'domain' => 'example.com',
        'expirationDate' => '2028-10-30 10:41:05',
        'cost' => 1108,
        'orderId' => 123456789,
        'balance' => 10000,
        'limits' => [
            'attempts' => [
                'TTL' => 1,
                'limit' => 1,
                'used' => 1,
            ],
            'success' => [
                'TTL' => 86400,
                'limit' => 1000,
                'used' => 3,
            ],
        ],
    ];

    $domainRenewal = DomainRenewal::fromArray($data);

    expect($domainRenewal->domain)->toBe('example.com')
        ->and($domainRenewal->costInCents)->toBe(1108)
        ->and($domainRenewal->orderId)->toBe(123456789)
        ->and($domainRenewal->balanceInCents)->toBe(10000)
        ->and($domainRenewal->expirationDate?->format('Y-m-d H:i:s'))->toBe('2028-10-30 10:41:05')
        ->and($domainRenewal->hasLimits)->toBeTrue();
});

test('it handles missing limits and expiration date', function (): void {
    $domainRenewal = DomainRenewal::fromArray([
        'domain' => 'example.com',
        'cost' => 500,
        'orderId' => 1,
        'balance' => 100,
    ]);

    expect($domainRenewal->expirationDate)->toBeNull()
        ->and($domainRenewal->hasLimits)->toBeFalse()
        ->and($domainRenewal->remainingAttempts)->toBeNull()
        ->and($domainRenewal->remainingRenewals)->toBeNull();
});

test('it ignores an invalid expiration date', function (): void {
    $domainRenewal = DomainRenewal::fromArray([
        'domain' => 'example.com',
        'expirationDate' => 'not-a-date',
        'cost' => 500,
        'orderId' => 1,
        'balance' => 100,
    ]);

    expect($domainRenewal->expirationDate)->toBeNull();
});

test('costInDollars converts cents to dollars', function (): void {
    $domainRenewal = DomainRenewal::fromArray([
        'domain' => 'example.com',
        'cost' => 1108,
        'orderId' => 1,
        'balance' => 0,
    ]);

    expect($domainRenewal->costInDollars)->toBe(11.08);
});

test('balanceInDollars converts cents to dollars', function (): void {
    $domainRenewal = DomainRenewal::fromArray([
        'domain' => 'example.com',
        'cost' => 0,
        'orderId' => 1,
        'balance' => 10000,
    ]);

    expect($domainRenewal->balanceInDollars)->toBe(100.00);
});

test('remainingAttempts calculates correctly', function (): void {
    $domainRenewal = DomainRenewal::fromArray([
        'domain' => 'example.com',
        'cost' => 0,
        'orderId' => 1,
        'balance' => 0,
        'limits' => [
            'attempts' => ['limit' => 5, 'used' => 3],
        ],
    ]);

    expect($domainRenewal->remainingAttempts)->toBe(2);
});

test('remainingRenewals calculates correctly', function (): void {
    $domainRenewal = DomainRenewal::fromArray([
        'domain' => 'example.com',
        'cost' => 0,
        'orderId' => 1,
        'balance' => 0,
        'limits' => [
            'success' => ['limit' => 10, 'used' => 7],
        ],
    ]);

    expect($domainRenewal->remainingRenewals)->toBe(3);
});

test('toArray serializes correctly', function (): void {
    $domainRenewal = DomainRenewal::fromArray([
        'domain' => 'example.com',
        'expirationDate' => '2028-10-30T10:41:05+00:00',
        'cost' => 1108,
        'orderId' => 123,
        'balance' => 5000,
        'limits' => ['attempts' => ['limit' => 1, 'used' => 1]],
    ]);

    expect($domainRenewal->toArray())->toBe([
        'domain' => 'example.com',
        'costInCents' => 1108,
        'orderId' => 123,
        'balanceInCents' => 5000,
        'expirationDate' => '2028-10-30T10:41:05+00:00',
        'limits' => ['attempts' => ['limit' => 1, 'used' => 1]],
    ]);
});

test('toArray omits null expiration date and limits', function (): void {
    $domainRenewal = DomainRenewal::fromArray([
        'domain' => 'example.com',
        'cost' => 500,
        'orderId' => 1,
        'balance' => 100,
    ]);

    expect($domainRenewal->toArray())->not->toHaveKey('expirationDate')
        ->and($domainRenewal->toArray())->not->toHaveKey('limits');
});

test('jsonSerialize returns toArray', function (): void {
    $domainRenewal = DomainRenewal::fromArray([
        'domain' => 'example.com',
        'cost' => 500,
        'orderId' => 1,
        'balance' => 100,
    ]);

    expect($domainRenewal->jsonSerialize())->toBe($domainRenewal->toArray());
});
