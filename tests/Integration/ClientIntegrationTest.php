<?php

declare(strict_types=1);

use Porkbun\Api\Dns;
use Porkbun\Api\Ping;
use Porkbun\Api\Pricing;
use Porkbun\Api\Ssl;
use Porkbun\Client;
use Porkbun\DTO\PingData;
use Porkbun\DTO\PricingCollection;
use Porkbun\Enum\Endpoint;
use Porkbun\Exception\AuthenticationException;

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
    $pricing = new Pricing($httpClient);

    $pricingCollection = $pricing->all();

    expect($pricingCollection)->toBeInstanceOf(PricingCollection::class)
        ->and($pricingCollection->getRegistrationPrice('com'))->toBe(8.68)
        ->and($pricingCollection->getRenewalPrice('com'))->toBe(8.68);
});

test('client integration - auth required endpoint throws without credentials', function (): void {
    $mockClient = createMockHttpClient([
        ['body' => ['status' => 'ERROR'], 'httpStatus' => 403],
    ]);

    $httpClient = createHttpClient($mockClient);

    expect(fn (): PingData => new Ping($httpClient)->check())
        ->toThrow(AuthenticationException::class);
});

test('client integration - auth required endpoint works with credentials', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'yourIp' => '192.0.2.1'],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $ping = new Ping($httpClient);

    $pingData = $ping->check();

    expect($pingData)->toBeInstanceOf(PingData::class)
        ->and($pingData->yourIp)->toBe('192.0.2.1');
});

test('client integration - dynamic configuration changes', function (): void {
    $client = Client::create();

    expect($client->getEndpoint())->toBe(Endpoint::DEFAULT);
    expect($client->isAuthenticated())->toBeFalse();

    $client->useIpv4Endpoint();
    expect($client->getEndpoint())->toBe(Endpoint::IPV4);

    $client->authenticate('pk1_key', 'sk1_secret');
    expect($client->isAuthenticated())->toBeTrue();

    $client->clearAuth();
    expect($client->isAuthenticated())->toBeFalse();
});

test('client integration - domain specific services get domain parameter', function (): void {
    $client = Client::create();

    $dnsService = $client->dns('example.com');
    $sslService = $client->ssl('example.com');

    expect($dnsService)->toBeInstanceOf(Dns::class)
        ->and($dnsService->getDomain())->toBe('example.com');

    expect($sslService)->toBeInstanceOf(Ssl::class)
        ->and($sslService->getDomain())->toBe('example.com');
});
