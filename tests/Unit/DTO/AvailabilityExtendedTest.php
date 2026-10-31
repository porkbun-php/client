<?php

declare(strict_types=1);

use Porkbun\DTO\AvailabilityResult as Availability;

test('getPriceInCents returns price in cents', function (): void {
    $availability = Availability::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'standard', 'price' => '8.68'],
    ]);

    expect($availability->priceInCents)->toBe(868);
});

test('getPriceInCents returns null when no price', function (): void {
    $availability = Availability::fromArray([
        'response' => ['avail' => 'no', 'type' => 'standard'],
    ]);

    expect($availability->priceInCents)->toBeNull();
});

test('getPriceInCents handles fractional cents correctly', function (): void {
    $availability = Availability::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'standard', 'price' => '12.345'],
    ]);

    expect($availability->priceInCents)->toBe(1235);
});

test('getPriceInCents falls back to regularPrice', function (): void {
    $availability = Availability::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'standard', 'regularPrice' => '15.00'],
    ]);

    expect($availability->priceInCents)->toBe(1500);
});

test('renewalPriceInCents returns renewal price in cents', function (): void {
    $availability = Availability::fromArray([
        'response' => [
            'avail' => 'no',
            'type' => 'registration',
            'additional' => ['renewal' => ['type' => 'renewal', 'price' => '11.08', 'regularPrice' => '12.00']],
        ],
    ]);

    expect($availability->renewalPriceInCents)->toBe(1108);
});

test('renewalPriceInCents falls back to renewal regularPrice', function (): void {
    $availability = Availability::fromArray([
        'response' => [
            'avail' => 'no',
            'type' => 'registration',
            'additional' => ['renewal' => ['type' => 'renewal', 'regularPrice' => '12.00']],
        ],
    ]);

    expect($availability->renewalPriceInCents)->toBe(1200);
});

test('renewalPriceInCents returns null when no renewal price', function (): void {
    $availability = Availability::fromArray([
        'response' => ['avail' => 'no', 'type' => 'registration'],
    ]);

    expect($availability->renewalPriceInCents)->toBeNull();
});
