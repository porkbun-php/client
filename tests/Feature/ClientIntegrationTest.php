<?php

declare(strict_types=1);

use Porkbun\Api\Dns;
use Porkbun\Api\Pricing;
use Porkbun\Api\Ssl;
use Porkbun\Client;
use Porkbun\DTO\PricingCollection;
use Porkbun\Enum\Endpoint;
use Porkbun\Exception\AuthenticationException;
use Porkbun\Resource\Domain;

test('client integration - public pricing works without auth', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'pricing' => [
                'com' => ['registration' => '8.68', 'renewal' => '8.68'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient);
    $pricing = new Pricing(createMockContext($httpClient));

    $pricingCollection = $pricing->all();

    expect($pricingCollection)->toBeInstanceOf(PricingCollection::class)
        ->and($pricingCollection->find('com')?->registrationPrice)->toBe(8.68)
        ->and($pricingCollection->find('com')?->renewalPrice)->toBe(8.68);
});

test('client integration - auth required endpoint throws without credentials', function (): void {
    $mockClient = createMockHttpClient([
        ['body' => ['status' => 'ERROR'], 'httpStatus' => 403],
    ]);

    $httpClient = createHttpClient($mockClient);
    $pricing = new Pricing(createMockContext($httpClient));

    expect(fn (): PricingCollection => $pricing->all())
        ->toThrow(AuthenticationException::class);
});

test('client integration - auth required endpoint works with credentials', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'pricing' => ['com' => ['registration' => '8.68']]],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $pricing = new Pricing(createMockContext($httpClient));

    $pricingCollection = $pricing->all();

    expect($pricingCollection)->toBeInstanceOf(PricingCollection::class);
});

test('client integration - dynamic configuration changes', function (): void {
    $client = new Client();

    expect($client->endpoint)->toBe(Endpoint::DEFAULT);
    expect($client->isAuthenticated())->toBeFalse();

    $client->useIpv4Endpoint();
    expect($client->endpoint)->toBe(Endpoint::IPV4);

    $client->authenticate('pk1_key', 'sk1_secret');
    expect($client->isAuthenticated())->toBeTrue();

    $client->clearAuth();
    expect($client->isAuthenticated())->toBeFalse();
});

test('client integration - domain facade provides domain specific services', function (): void {
    $client = new Client();

    $domain = $client->domain('example.com');

    expect($domain)->toBeInstanceOf(Domain::class)
        ->and($domain->name)->toBe('example.com');

    expect($domain->dns())->toBeInstanceOf(Dns::class);
    expect($domain->ssl())->toBeInstanceOf(Ssl::class);
});
