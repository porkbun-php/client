<?php

declare(strict_types=1);

use Porkbun\Api\Pricing;
use Porkbun\DTO\PricingCollection;

test('pricing api returns pricing collection', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'pricing' => [
                'com' => ['registration' => '8.68', 'renewal' => '8.68'],
                'net' => ['registration' => '9.68', 'renewal' => '9.68'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient);
    $pricing = new Pricing($httpClient);

    $pricingCollection = $pricing->all();

    expect($pricingCollection)->toBeInstanceOf(PricingCollection::class)
        ->and($pricingCollection->has('com'))->toBeTrue()
        ->and($pricingCollection->has('net'))->toBeTrue()
        ->and($pricingCollection->getRegistrationPrice('com'))->toBe(8.68)
        ->and($pricingCollection->getRenewalPrice('com'))->toBe(8.68);
});

test('pricing api handles empty pricing', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'pricing' => []],
    ]);

    $httpClient = createHttpClient($mockClient);
    $pricing = new Pricing($httpClient);

    $pricingCollection = $pricing->all();

    expect($pricingCollection)->toBeInstanceOf(PricingCollection::class)
        ->and($pricingCollection->isEmpty())->toBeTrue()
        ->and($pricingCollection->count())->toBe(0);
});
