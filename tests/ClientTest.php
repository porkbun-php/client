<?php

declare(strict_types=1);

use Porkbun\Client;
use Porkbun\Config;
use Porkbun\Service\AuthService;
use Porkbun\Service\DnsService;
use Porkbun\Service\DomainService;
use Porkbun\Service\PricingService;
use Porkbun\Service\SslService;

test('client can be instantiated without config', function (): void {
    $client = new Client();

    expect($client)->toBeInstanceOf(Client::class);
    expect($client->getConfig())->toBeInstanceOf(Config::class);
});

test('client can be instantiated with config', function (): void {
    $config = new Config('https://custom.api.com', 'test-key', 'test-secret');
    $client = new Client($config);

    expect($client->getConfig())->toBe($config);
    expect($client->getConfig()->getBaseUrl())->toBe('https://custom.api.com');
});

test('client can set base url dynamically', function (): void {
    $client = new Client();
    $client->setBaseUrl('https://api-ipv4.porkbun.com/api/json/v3');

    expect($client->getConfig()->getBaseUrl())->toBe('https://api-ipv4.porkbun.com/api/json/v3');
});

test('client can set and clear auth dynamically', function (): void {
    $client = new Client();

    expect($client->getConfig()->hasAuth())->toBeFalse();

    $client->setAuth('pk1_key', 'sk1_secret');
    expect($client->getConfig()->hasAuth())->toBeTrue();

    $client->clearAuth();
    expect($client->getConfig()->hasAuth())->toBeFalse();
});

test('client provides service factories', function (): void {
    $client = new Client();

    expect($client->pricing())->toBeInstanceOf(PricingService::class);
    expect($client->auth())->toBeInstanceOf(AuthService::class);
    expect($client->domains())->toBeInstanceOf(DomainService::class);
    expect($client->dns('example.com'))->toBeInstanceOf(DnsService::class);
    expect($client->ssl('example.com'))->toBeInstanceOf(SslService::class);
});
