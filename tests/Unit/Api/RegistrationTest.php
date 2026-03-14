<?php

declare(strict_types=1);

use Porkbun\Api\Registration;
use Porkbun\DTO\DomainRegistration;

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
        ->and($domainRegistration->costInCents)->toBe(868)
        ->and($domainRegistration->orderId)->toBe(123456)
        ->and($domainRegistration->balanceInCents)->toBe(5000);
});
