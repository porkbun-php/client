<?php

declare(strict_types=1);

use Porkbun\Config;

test('config can be instantiated with defaults', function (): void {
    $config = new Config();

    expect($config->getBaseUrl())->toBe('https://api.porkbun.com/api/json/v3');
    expect($config->hasAuth())->toBeFalse();
    expect($config->getTimeout())->toBe(30);
});

test('config can be instantiated with custom values', function (): void {
    $config = new Config(
        baseUrl: 'https://custom.api.com',
        apiKey: 'test-key',
        secretKey: 'test-secret',
        timeout: 60
    );

    expect($config->getBaseUrl())->toBe('https://custom.api.com');
    expect($config->hasAuth())->toBeTrue();
    expect($config->getTimeout())->toBe(60);
});

test('config can set base url', function (): void {
    $config = new Config();
    $config->setBaseUrl('https://new.api.com/v3/');

    expect($config->getBaseUrl())->toBe('https://new.api.com/v3');
});

test('config can set and clear auth', function (): void {
    $config = new Config();

    expect($config->hasAuth())->toBeFalse();

    $config->setAuth('pk1_key', 'sk1_secret');
    expect($config->hasAuth())->toBeTrue();

    $payload = $config->getAuthPayload();
    expect($payload)->toHaveKey('apikey', 'pk1_key');
    expect($payload)->toHaveKey('secretapikey', 'sk1_secret');

    $config->clearAuth();
    expect($config->hasAuth())->toBeFalse();
    expect($config->getAuthPayload())->toBeEmpty();
});

test('config can set timeout', function (): void {
    $config = new Config();
    $config->setTimeout(120);

    expect($config->getTimeout())->toBe(120);
});
