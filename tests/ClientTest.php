<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use Porkbun\Api\Dns;
use Porkbun\Api\Domain;
use Porkbun\Api\Ping;
use Porkbun\Api\Pricing;
use Porkbun\Api\Ssl;
use Porkbun\Client;
use Porkbun\Enum\Endpoint;
use Porkbun\Exception\InvalidArgumentException;
use Psr\Http\Client\ClientInterface;

test('client can be instantiated with PSR interfaces', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $factory = new Psr17Factory();

    $client = new Client($mock, $factory, $factory);

    expect($client)->toBeInstanceOf(Client::class);
});

test('client can be created with static factory', function (): void {
    $client = Client::create();

    expect($client)->toBeInstanceOf(Client::class)
        ->and($client->isAuthenticated())->toBeFalse();
});

test('client can be created with authentication', function (): void {
    $client = Client::create('pk1_key', 'sk1_secret');

    expect($client->isAuthenticated())->toBeTrue();
});

test('client rejects partial authentication', function (): void {
    expect(fn (): Client => Client::create('pk1_key'))
        ->toThrow(InvalidArgumentException::class, 'Both apiKey and secretKey must be provided together');

    expect(fn (): Client => Client::create(null, 'sk1_secret'))
        ->toThrow(InvalidArgumentException::class, 'Both apiKey and secretKey must be provided together');
});

test('client can set and clear auth dynamically', function (): void {
    $client = Client::create();

    expect($client->isAuthenticated())->toBeFalse();

    $client->authenticate('pk1_key', 'sk1_secret');
    expect($client->isAuthenticated())->toBeTrue();

    $client->clearAuth();
    expect($client->isAuthenticated())->toBeFalse();
});

test('client provides API factories', function (): void {
    $client = Client::create();

    expect($client->pricing())->toBeInstanceOf(Pricing::class)
        ->and($client->ping())->toBeInstanceOf(Ping::class)
        ->and($client->domains())->toBeInstanceOf(Domain::class)
        ->and($client->dns('example.com'))->toBeInstanceOf(Dns::class)
        ->and($client->ssl('example.com'))->toBeInstanceOf(Ssl::class);
});

test('client can switch to ipv4 endpoint', function (): void {
    $client = Client::create();

    expect($client->getEndpoint())->toBe(Endpoint::DEFAULT);

    $client->useIpv4Endpoint();
    expect($client->getEndpoint())->toBe(Endpoint::IPV4);

    $client->useDefaultEndpoint();
    expect($client->getEndpoint())->toBe(Endpoint::DEFAULT);
});

test('client can use custom endpoint', function (): void {
    $client = Client::create();

    $client->useEndpoint(Endpoint::IPV4);
    expect($client->getEndpoint())->toBe(Endpoint::IPV4);
});

test('client endpoint methods are fluent', function (): void {
    $client = Client::create();

    $result1 = $client->useIpv4Endpoint();
    expect($result1)->toBe($client);

    $result2 = $client->useDefaultEndpoint();
    expect($result2)->toBe($client);

    $result3 = $client->authenticate('key', 'secret');
    expect($result3)->toBe($client);

    $result4 = $client->clearAuth();
    expect($result4)->toBe($client);
});
