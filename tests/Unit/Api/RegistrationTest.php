<?php

declare(strict_types=1);

use Porkbun\Api\Registration;
use Porkbun\DTO\DomainRegistration;
use Porkbun\Exception\InvalidArgumentException;

test('registration api can register domain', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'domain' => 'newdomain.com',
            'cost' => 868,
            'orderId' => 123456,
            'balance' => 5000,
            'limits' => [
                'attempts' => ['limit' => 1, 'used' => 1],
                'success' => ['limit' => 10, 'used' => 1],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $registration = new Registration(createMockContext($httpClient), 'newdomain.com');

    $domainRegistration = $registration->register(868);

    expect($domainRegistration)->toBeInstanceOf(DomainRegistration::class)
        ->and($domainRegistration->domain)->toBe('newdomain.com')
        ->and($domainRegistration->cost)->toBe(868)
        ->and($domainRegistration->orderId)->toBe(123456)
        ->and($domainRegistration->balance)->toBe(5000);
});

test('registration api accepts options', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'domain' => 'newdomain.com',
            'cost' => 868,
            'orderId' => 123456,
            'balance' => 5000,
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $registration = new Registration(createMockContext($httpClient), 'newdomain.com');

    $domainRegistration = $registration->register(868, [
        'years' => 2,
        'addPrivacy' => true,
        'coupon' => 'SAVE10',
        'ns' => ['ns1.custom.com', 'ns2.custom.com'],
    ]);

    expect($domainRegistration)->toBeInstanceOf(DomainRegistration::class)
        ->and($domainRegistration->domain)->toBe('newdomain.com');
});

test('registration api handles whois options', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'domain' => 'newdomain.com',
            'cost' => 868,
            'orderId' => 123456,
            'balance' => 5000,
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $registration = new Registration(createMockContext($httpClient), 'newdomain.com');

    $domainRegistration = $registration->register(868, [
        'whois' => [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
        ],
    ]);

    expect($domainRegistration)->toBeInstanceOf(DomainRegistration::class);
});

test('registration api rejects unknown option key', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $registration = new Registration(createMockContext($httpClient), 'newdomain.com');

    expect(fn (): DomainRegistration => $registration->register(868, ['year' => 2]))
        ->toThrow(InvalidArgumentException::class, 'Unknown registration option(s): year');
});

test('registration api rejects multiple unknown keys', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $registration = new Registration(createMockContext($httpClient), 'newdomain.com');

    expect(fn (): DomainRegistration => $registration->register(868, ['year' => 2, 'privacy' => true]))
        ->toThrow(InvalidArgumentException::class, 'Unknown registration option(s): year, privacy');
});
