<?php

declare(strict_types=1);

use Porkbun\DTO\DomainCheckData;

test('it creates domain check data from array', function (): void {
    $domainCheckData = DomainCheckData::fromArray([
        'response' => [
            'avail' => 'yes',
            'type' => 'registration',
            'price' => '8.99',
            'regularPrice' => '12.99',
            'firstYearPromo' => 'yes',
            'premium' => 'no',
        ],
    ]);

    expect($domainCheckData->isAvailable)->toBeTrue()
        ->and($domainCheckData->type)->toBe('registration')
        ->and($domainCheckData->price)->toBe(8.99)
        ->and($domainCheckData->regularPrice)->toBe(12.99)
        ->and($domainCheckData->hasFirstYearPromo)->toBeTrue()
        ->and($domainCheckData->isPremium)->toBeFalse();
});

test('it handles unavailable domain', function (): void {
    $domainCheckData = DomainCheckData::fromArray([
        'response' => [
            'avail' => 'no',
            'type' => 'registration',
        ],
    ]);

    expect($domainCheckData->isAvailable)->toBeFalse();
});

test('it parses renewal and transfer prices', function (): void {
    $domainCheckData = DomainCheckData::fromArray([
        'response' => [
            'avail' => 'yes',
            'type' => 'registration',
            'additional' => [
                'renewal' => ['price' => '10.99'],
                'transfer' => ['price' => '9.99'],
            ],
        ],
    ]);

    expect($domainCheckData->renewalPrice)->toBe(10.99)
        ->and($domainCheckData->transferPrice)->toBe(9.99);
});

test('it parses rate limit info', function (): void {
    $domainCheckData = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration'],
        'limits' => [
            'limit' => 100,
            'used' => 25,
        ],
    ]);

    expect($domainCheckData->limitTotal)->toBe(100)
        ->and($domainCheckData->limitUsed)->toBe(25);
});

test('hasPromoPrice detects promotional pricing', function (): void {
    $domainCheckData = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration', 'price' => '5.99', 'regularPrice' => '12.99'],
    ]);
    $withoutPromo = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration', 'price' => '12.99', 'regularPrice' => '12.99'],
    ]);
    $noPrice = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration'],
    ]);

    expect($domainCheckData->hasPromoPrice())->toBeTrue()
        ->and($withoutPromo->hasPromoPrice())->toBeFalse()
        ->and($noPrice->hasPromoPrice())->toBeFalse();
});

test('getPromoSavings calculates savings', function (): void {
    $domainCheckData = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration', 'price' => '5.99', 'regularPrice' => '12.99'],
    ]);
    $noPromo = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration', 'price' => '12.99', 'regularPrice' => '12.99'],
    ]);

    expect($domainCheckData->getPromoSavings())->toBe(7.0)
        ->and($noPromo->getPromoSavings())->toBeNull();
});

test('getEffectivePrice returns price or regular price', function (): void {
    $domainCheckData = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration', 'price' => '5.99', 'regularPrice' => '12.99'],
    ]);
    $onlyRegular = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration', 'regularPrice' => '12.99'],
    ]);
    $noPrice = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration'],
    ]);

    expect($domainCheckData->getEffectivePrice())->toBe(5.99)
        ->and($onlyRegular->getEffectivePrice())->toBe(12.99)
        ->and($noPrice->getEffectivePrice())->toBeNull();
});

test('hasRateLimitInfo checks for limit data', function (): void {
    $domainCheckData = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration'],
        'limits' => ['limit' => 100, 'used' => 25],
    ]);
    $noLimits = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration'],
    ]);

    expect($domainCheckData->hasRateLimitInfo())->toBeTrue()
        ->and($noLimits->hasRateLimitInfo())->toBeFalse();
});

test('getRemainingChecks calculates remaining', function (): void {
    $domainCheckData = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration'],
        'limits' => ['limit' => 100, 'used' => 25],
    ]);

    expect($domainCheckData->getRemainingChecks())->toBe(75);
});

test('getRemainingChecks returns null without limits', function (): void {
    $domainCheckData = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration'],
    ]);

    expect($domainCheckData->getRemainingChecks())->toBeNull();
});

test('getRateLimitUsagePercentage calculates percentage', function (): void {
    $domainCheckData = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration'],
        'limits' => ['limit' => 100, 'used' => 25],
    ]);

    expect($domainCheckData->getRateLimitUsagePercentage())->toBe(25.0);
});

test('getRateLimitUsagePercentage handles zero limit', function (): void {
    $domainCheckData = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration'],
        'limits' => ['limit' => 0, 'used' => 0],
    ]);

    expect($domainCheckData->getRateLimitUsagePercentage())->toBeNull();
});

test('isRateLimitNearExhausted detects high usage', function (): void {
    $domainCheckData = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration'],
        'limits' => ['limit' => 100, 'used' => 85],
    ]);
    $low = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration'],
        'limits' => ['limit' => 100, 'used' => 50],
    ]);

    expect($domainCheckData->isRateLimitNearExhausted())->toBeTrue()
        ->and($low->isRateLimitNearExhausted())->toBeFalse();
});

test('isAvailableAndAffordable checks both conditions', function (): void {
    $domainCheckData = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration', 'price' => '8.99'],
    ]);
    $unavailable = DomainCheckData::fromArray([
        'response' => ['avail' => 'no', 'type' => 'registration', 'price' => '8.99'],
    ]);
    $expensive = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration', 'price' => '50.00'],
    ]);

    expect($domainCheckData->isAvailableAndAffordable(10.0))->toBeTrue()
        ->and($unavailable->isAvailableAndAffordable(10.0))->toBeFalse()
        ->and($expensive->isAvailableAndAffordable(10.0))->toBeFalse();
});

test('toArray serializes data correctly', function (): void {
    $domainCheckData = DomainCheckData::fromArray([
        'response' => [
            'avail' => 'yes',
            'type' => 'registration',
            'price' => '8.99',
            'regularPrice' => '12.99',
            'firstYearPromo' => 'yes',
            'premium' => 'yes',
            'additional' => [
                'renewal' => ['price' => '10.99'],
                'transfer' => ['price' => '9.99'],
            ],
        ],
        'limits' => ['limit' => 100, 'used' => 25],
        'additionalInfo' => ['key' => 'value'],
        'registrar' => 'porkbun',
        'expirationDate' => '2025-01-01',
        'whoisInfo' => ['registrant' => 'test'],
    ]);

    $array = $domainCheckData->toArray();

    expect($array['response']['avail'])->toBe('yes')
        ->and($array['response']['type'])->toBe('registration')
        ->and($array['response']['price'])->toBe(8.99)
        ->and($array['response']['firstYearPromo'])->toBe('yes')
        ->and($array['response']['premium'])->toBe('yes')
        ->and($array['limits']['limit'])->toBe(100)
        ->and($array['additionalInfo'])->toBe(['key' => 'value'])
        ->and($array['registrar'])->toBe('porkbun');
});

test('jsonSerialize returns toArray', function (): void {
    $domainCheckData = DomainCheckData::fromArray([
        'response' => ['avail' => 'yes', 'type' => 'registration'],
    ]);

    expect($domainCheckData->jsonSerialize())->toBe($domainCheckData->toArray());
});
