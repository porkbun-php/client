<?php

declare(strict_types=1);

use Porkbun\DTO\AvailabilityResult as Availability;

test('it creates availability from real API response (unavailable domain)', function (): void {
    $availability = Availability::fromArray([
        'status' => 'SUCCESS',
        'response' => [
            'avail' => 'no',
            'type' => 'registration',
            'price' => '10.81',
            'firstYearPromo' => 'yes',
            'regularPrice' => '12.87',
            'premium' => 'no',
            'additional' => [
                'renewal' => [
                    'type' => 'renewal',
                    'price' => '12.87',
                    'regularPrice' => '12.87',
                ],
                'transfer' => [
                    'type' => 'transfer',
                    'price' => '12.87',
                    'regularPrice' => '12.87',
                ],
            ],
            'minDuration' => 1,
        ],
        'limits' => [
            'TTL' => 10,
            'limit' => 1,
            'used' => 1,
            'naturalLanguage' => '1 out of 1 checks within 10 seconds used.',
        ],
    ]);

    expect($availability->isAvailable)->toBeFalse()
        ->and($availability->type)->toBe('registration')
        ->and($availability->price)->toBe(10.81)
        ->and($availability->regularPrice)->toBe(12.87)
        ->and($availability->hasFirstYearPromo)->toBeTrue()
        ->and($availability->isPremium)->toBeFalse()
        ->and($availability->minDuration)->toBe(1)
        ->and($availability->renewalPrice)->toBe(12.87)
        ->and($availability->renewalRegularPrice)->toBe(12.87)
        ->and($availability->transferPrice)->toBe(12.87)
        ->and($availability->transferRegularPrice)->toBe(12.87)
        ->and($availability->limitTotal)->toBe(1)
        ->and($availability->limitUsed)->toBe(1)
        ->and($availability->limitTtl)->toBe(10)
        ->and($availability->limitNaturalLanguage)->toBe('1 out of 1 checks within 10 seconds used.');
});

test('it creates availability from real API response (available domain)', function (): void {
    $availability = Availability::fromArray([
        'status' => 'SUCCESS',
        'response' => [
            'avail' => 'yes',
            'type' => 'registration',
            'price' => '28.12',
            'firstYearPromo' => 'yes',
            'regularPrice' => '51.80',
            'premium' => 'no',
            'additional' => [
                'renewal' => [
                    'type' => 'renewal',
                    'price' => '51.80',
                    'regularPrice' => '51.80',
                ],
                'transfer' => [
                    'type' => 'transfer',
                    'price' => '51.80',
                    'regularPrice' => '51.80',
                ],
            ],
            'minDuration' => 1,
        ],
        'limits' => [
            'TTL' => 10,
            'limit' => 1,
            'used' => 1,
            'naturalLanguage' => '1 out of 1 checks within 10 seconds used.',
        ],
    ]);

    expect($availability->isAvailable)->toBeTrue()
        ->and($availability->type)->toBe('registration')
        ->and($availability->price)->toBe(28.12)
        ->and($availability->regularPrice)->toBe(51.80)
        ->and($availability->hasFirstYearPromo)->toBeTrue()
        ->and($availability->isPremium)->toBeFalse()
        ->and($availability->minDuration)->toBe(1);
});

test('it handles minimal response', function (): void {
    $availability = Availability::fromArray([
        'response' => [
            'avail' => 'no',
            'type' => 'registration',
        ],
    ]);

    expect($availability->isAvailable)->toBeFalse()
        ->and($availability->type)->toBe('registration')
        ->and($availability->price)->toBeNull()
        ->and($availability->minDuration)->toBe(1)
        ->and($availability->limitTotal)->toBeNull();
});

test('hasPromoPrice detects promotional pricing', function (): void {
    $availability = Availability::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration', 'price' => '5.99', 'regularPrice' => '12.99'],
    ]);
    $withoutPromo = Availability::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration', 'price' => '12.99', 'regularPrice' => '12.99'],
    ]);
    $noPrice = Availability::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration'],
    ]);

    expect($availability->hasPromoPrice)->toBeTrue()
        ->and($withoutPromo->hasPromoPrice)->toBeFalse()
        ->and($noPrice->hasPromoPrice)->toBeFalse();
});

test('hasRenewalPromo detects renewal promotional pricing', function (): void {
    $availability = Availability::fromArray([
        'response' => [
            'avail' => 'yes',
            'type' => 'registration',
            'additional' => [
                'renewal' => ['price' => '8.99', 'regularPrice' => '12.99'],
            ],
        ],
    ]);
    $withoutPromo = Availability::fromArray([
        'response' => [
            'avail' => 'yes',
            'type' => 'registration',
            'additional' => [
                'renewal' => ['price' => '12.99', 'regularPrice' => '12.99'],
            ],
        ],
    ]);

    expect($availability->hasRenewalPromo)->toBeTrue()
        ->and($withoutPromo->hasRenewalPromo)->toBeFalse();
});

test('hasTransferPromo detects transfer promotional pricing', function (): void {
    $availability = Availability::fromArray([
        'response' => [
            'avail' => 'yes',
            'type' => 'registration',
            'additional' => [
                'transfer' => ['price' => '8.99', 'regularPrice' => '12.99'],
            ],
        ],
    ]);
    $withoutPromo = Availability::fromArray([
        'response' => [
            'avail' => 'yes',
            'type' => 'registration',
            'additional' => [
                'transfer' => ['price' => '12.99', 'regularPrice' => '12.99'],
            ],
        ],
    ]);

    expect($availability->hasTransferPromo)->toBeTrue()
        ->and($withoutPromo->hasTransferPromo)->toBeFalse();
});

test('getPromoSavings calculates savings', function (): void {
    $availability = Availability::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration', 'price' => '5.99', 'regularPrice' => '12.99'],
    ]);
    $noPromo = Availability::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration', 'price' => '12.99', 'regularPrice' => '12.99'],
    ]);

    expect($availability->promoSavings)->toBe(7.0)
        ->and($noPromo->promoSavings)->toBeNull();
});

test('getEffectivePrice returns price or regular price', function (): void {
    $availability = Availability::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration', 'price' => '5.99', 'regularPrice' => '12.99'],
    ]);
    $onlyRegular = Availability::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration', 'regularPrice' => '12.99'],
    ]);
    $noPrice = Availability::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration'],
    ]);

    expect($availability->effectivePrice)->toBe(5.99)
        ->and($onlyRegular->effectivePrice)->toBe(12.99)
        ->and($noPrice->effectivePrice)->toBeNull();
});

test('hasRateLimitInfo checks for limit data', function (): void {
    $availability = Availability::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration'],
        'limits' => ['limit' => 100, 'used' => 25],
    ]);
    $noLimits = Availability::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration'],
    ]);

    expect($availability->hasRateLimitInfo)->toBeTrue()
        ->and($noLimits->hasRateLimitInfo)->toBeFalse();
});

test('getRemainingChecks calculates remaining', function (): void {
    $availability = Availability::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration'],
        'limits' => ['limit' => 100, 'used' => 25],
    ]);

    expect($availability->remainingChecks)->toBe(75);
});

test('getRemainingChecks returns null without limits', function (): void {
    $availability = Availability::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration'],
    ]);

    expect($availability->remainingChecks)->toBeNull();
});

test('toArray serializes data correctly', function (): void {
    $availability = Availability::fromArray([
        'response' => [
            'avail' => 'yes',
            'type' => 'registration',
            'price' => '8.99',
            'regularPrice' => '12.99',
            'firstYearPromo' => 'yes',
            'premium' => 'yes',
            'minDuration' => 2,
            'additional' => [
                'renewal' => ['price' => '10.99', 'regularPrice' => '11.99'],
                'transfer' => ['price' => '9.99', 'regularPrice' => '10.99'],
            ],
        ],
        'limits' => ['limit' => 100, 'used' => 25, 'TTL' => 10, 'naturalLanguage' => 'test'],
    ]);

    $array = $availability->toArray();

    expect($array['isAvailable'])->toBeTrue()
        ->and($array['type'])->toBe('registration')
        ->and($array['price'])->toBe(8.99)
        ->and($array['regularPrice'])->toBe(12.99)
        ->and($array['hasFirstYearPromo'])->toBeTrue()
        ->and($array['isPremium'])->toBeTrue()
        ->and($array['minDuration'])->toBe(2)
        ->and($array['renewalPrice'])->toBe(10.99)
        ->and($array['renewalRegularPrice'])->toBe(11.99)
        ->and($array['transferPrice'])->toBe(9.99)
        ->and($array['transferRegularPrice'])->toBe(10.99)
        ->and($array['limitTotal'])->toBe(100)
        ->and($array['limitUsed'])->toBe(25)
        ->and($array['limitTtl'])->toBe(10)
        ->and($array['limitNaturalLanguage'])->toBe('test');
});

test('toArray round-trips regularPrice without price for renewal and transfer', function (): void {
    $original = Availability::fromArray([
        'response' => [
            'avail' => 'yes',
            'type' => 'registration',
            'additional' => [
                'renewal' => ['regularPrice' => '12.99'],
                'transfer' => ['regularPrice' => '14.99'],
            ],
        ],
    ]);

    expect($original->renewalPrice)->toBeNull()
        ->and($original->renewalRegularPrice)->toBe(12.99)
        ->and($original->transferPrice)->toBeNull()
        ->and($original->transferRegularPrice)->toBe(14.99);

    $roundTripped = Availability::fromArray($original->toArray());

    expect($roundTripped->renewalPrice)->toBeNull()
        ->and($roundTripped->renewalRegularPrice)->toBe(12.99)
        ->and($roundTripped->transferPrice)->toBeNull()
        ->and($roundTripped->transferRegularPrice)->toBe(14.99);
});

test('jsonSerialize returns toArray', function (): void {
    $availability = Availability::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration'],
    ]);

    expect($availability->jsonSerialize())->toBe($availability->toArray());
});
