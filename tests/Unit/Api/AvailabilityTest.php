<?php

declare(strict_types=1);

use Porkbun\Api\Availability;
use Porkbun\DTO\Availability as AvailabilityResult;

test('availability api can check domain availability', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'response' => [
                'avail' => 'yes',
                'type' => 'standard',
                'price' => '8.68',
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $availability = new Availability(createMockContext($httpClient), 'available-domain.com');

    $result = $availability->get();

    expect($result)->toBeInstanceOf(AvailabilityResult::class)
        ->and($result->isAvailable)->toBeTrue()
        ->and($result->type)->toBe('standard')
        ->and($result->price)->toBe(8.68);
});

test('availability api returns unavailable domain', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'response' => [
                'avail' => 'no',
                'type' => 'registration',
                'price' => '10.00',
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $availability = new Availability(createMockContext($httpClient), 'taken-domain.com');

    $result = $availability->get();

    expect($result->isAvailable)->toBeFalse()
        ->and($result->type)->toBe('registration');
});
