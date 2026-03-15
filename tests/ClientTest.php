<?php

declare(strict_types=1);

use Http\Discovery\ClassDiscovery;
use Porkbun\Api\Domains;
use Porkbun\Api\Pricing;
use Porkbun\Client;
use Porkbun\Enum\Endpoint;
use Porkbun\Exception\InvalidArgumentException;
use Porkbun\Resource\Domain;
use Psr\Http\Client\ClientInterface;

test('client can be instantiated with custom HTTP client', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $client = new Client($mock);

    expect($client)->toBeInstanceOf(Client::class);
});

test('client can be instantiated without arguments', function (): void {
    $client = new Client();

    expect($client)->toBeInstanceOf(Client::class)
        ->and($client->isAuthenticated())->toBeFalse();
});

test('client can authenticate after construction', function (): void {
    $client = new Client();
    $client->authenticate('pk1_key', 'sk1_secret');

    expect($client->isAuthenticated())->toBeTrue();
});

test('client can set and clear auth dynamically', function (): void {
    $client = new Client();

    expect($client->isAuthenticated())->toBeFalse();

    $client->authenticate('pk1_key', 'sk1_secret');
    expect($client->isAuthenticated())->toBeTrue();

    $client->clearAuth();
    expect($client->isAuthenticated())->toBeFalse();
});

test('client provides API factories', function (): void {
    $client = new Client();

    expect($client->pricing())->toBeInstanceOf(Pricing::class)
        ->and($client->domains())->toBeInstanceOf(Domains::class);
});

test('client provides domain facade', function (): void {
    $client = new Client();

    $domain = $client->domain('example.com');

    expect($domain)->toBeInstanceOf(Domain::class)
        ->and($domain->name)->toBe('example.com');
});

test('client can switch to ipv4 endpoint', function (): void {
    $client = new Client();

    expect($client->endpoint)->toBe(Endpoint::DEFAULT);

    $client->useIpv4Endpoint();
    expect($client->endpoint)->toBe(Endpoint::IPV4);

    $client->useDefaultEndpoint();
    expect($client->endpoint)->toBe(Endpoint::DEFAULT);
});

test('client can use custom endpoint', function (): void {
    $client = new Client();

    $client->useEndpoint(Endpoint::IPV4);
    expect($client->endpoint)->toBe(Endpoint::IPV4);
});

test('client cannot be cloned', function (): void {
    $client = new Client();
    expect(fn (): Client => clone $client)->toThrow(Error::class);
});

test('client throws clear error when no PSR-18 client is available', function (): void {
    $strategies = ClassDiscovery::getStrategies();
    ClassDiscovery::setStrategies([]);

    try {
        expect(fn (): Client => new Client())->toThrow(
            InvalidArgumentException::class,
            'No PSR-18 HTTP client found',
        );
    } finally {
        ClassDiscovery::setStrategies([...$strategies]);
    }
});

test('client endpoint methods are fluent', function (): void {
    $client = new Client();

    $result1 = $client->useIpv4Endpoint();
    expect($result1)->toBe($client);

    $result2 = $client->useDefaultEndpoint();
    expect($result2)->toBe($client);

    $result3 = $client->authenticate('key', 'secret');
    expect($result3)->toBe($client);

    $result4 = $client->clearAuth();
    expect($result4)->toBe($client);
});
