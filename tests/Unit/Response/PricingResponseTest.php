<?php

declare(strict_types=1);

use Porkbun\Response\PricingResponse;

test('pricing response can get all pricing data', function (): void {
    $data = [
        'status' => 'SUCCESS',
        'pricing' => [
            'com' => [
                'registration' => '8.68',
                'renewal' => '8.68',
            ],
            'net' => [
                'registration' => '9.98',
                'renewal' => '9.98',
            ],
        ],
    ];

    $response = new PricingResponse($data);

    expect($response->isSuccess())->toBeTrue();
    expect($response->getPricing())->toBe($data['pricing']);
    expect($response->getAllTlds())->toBe(['com', 'net']);
});

test('pricing response can get domain specific pricing', function (): void {
    $data = [
        'status' => 'SUCCESS',
        'pricing' => [
            'com' => [
                'registration' => '8.68',
                'renewal' => '8.68',
            ],
        ],
    ];

    $response = new PricingResponse($data);

    expect($response->getDomainPrice('com'))->toBe([
        'registration' => '8.68',
        'renewal' => '8.68',
    ]);
    expect($response->getDomainPrice('net'))->toBeNull();

    expect($response->getRegistrationPrice('com'))->toBe('8.68');
    expect($response->getRenewalPrice('com'))->toBe('8.68');
    expect($response->getRegistrationPrice('net'))->toBeNull();

    expect($response->hasDomain('com'))->toBeTrue();
    expect($response->hasDomain('net'))->toBeFalse();
});

test('pricing response handles empty data', function (): void {
    $data = ['status' => 'SUCCESS'];

    $response = new PricingResponse($data);

    expect($response->getPricing())->toBe([]);
    expect($response->getAllTlds())->toBe([]);
    expect($response->getDomainPrice('com'))->toBeNull();
    expect($response->hasDomain('com'))->toBeFalse();
});

test('pricing response handles error status', function (): void {
    $data = [
        'status' => 'ERROR',
        'message' => 'API error',
    ];

    $response = new PricingResponse($data);

    expect($response->isSuccess())->toBeFalse();
    expect($response->getStatus())->toBe('ERROR');
    expect($response->getMessage())->toBe('API error');
});
