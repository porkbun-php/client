<?php

declare(strict_types=1);

use Porkbun\Client;
use Porkbun\Config;
use Porkbun\Exception\AuthenticationException;
use Psr\Http\Client\ClientInterface;

test('client integration - public pricing works without auth', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();

    $responseData = [
        'status' => 'SUCCESS',
        'pricing' => [
            'com' => ['registration' => '8.68', 'renewal' => '8.68'],
        ],
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $client = new Client($config, $mock);
    $result = $client->pricing()->getPricing();

    expect($result)->toBe($responseData);
});

test('client integration - auth required endpoint throws without credentials', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();

    $response = mockApiResponse('');
    $response->shouldReceive('getStatusCode')->andReturn(403);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $client = new Client($config, $mock);

    expect(fn (): array => $client->auth()->ping())
        ->toThrow(AuthenticationException::class);
});

test('client integration - auth required endpoint works with credentials', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();

    $responseData = [
        'status' => 'SUCCESS',
        'yourIp' => '192.168.1.1',
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $client = new Client($config, $mock);
    $client->setAuth('pk1_key', 'sk1_secret');

    $result = $client->auth()->ping();

    expect($result)->toBe($responseData);
});

test('client integration - dynamic configuration changes', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $client = new Client(null, $mock);

    // Start with default config
    expect($client->getConfig()->getBaseUrl())->toBe('https://api.porkbun.com/api/json/v3');
    expect($client->getConfig()->hasAuth())->toBeFalse();

    // Change base URL
    $client->setBaseUrl('https://api-ipv4.porkbun.com/api/json/v3');
    expect($client->getConfig()->getBaseUrl())->toBe('https://api-ipv4.porkbun.com/api/json/v3');

    // Add auth
    $client->setAuth('pk1_key', 'sk1_secret');
    expect($client->getConfig()->hasAuth())->toBeTrue();
    expect($client->getConfig()->getAuthPayload())->toBe([
        'apikey' => 'pk1_key',
        'secretapikey' => 'sk1_secret',
    ]);

    // Clear auth
    $client->clearAuth();
    expect($client->getConfig()->hasAuth())->toBeFalse();
});

test('client integration - domain specific services get domain parameter', function (): void {
    $client = new Client();

    $dnsService = $client->dns('example.com');
    $sslService = $client->ssl('example.com');

    // Use reflection to verify domain was passed correctly
    $dnsReflection = new ReflectionClass($dnsService);
    $reflectionProperty = $dnsReflection->getProperty('domain');
    $reflectionProperty->setAccessible(true);
    expect($reflectionProperty->getValue($dnsService))->toBe('example.com');

    $sslReflection = new ReflectionClass($sslService);
    $sslProperty = $sslReflection->getProperty('domain');
    $sslProperty->setAccessible(true);
    expect($sslProperty->getValue($sslService))->toBe('example.com');
});

test('client integration - services share same config instance', function (): void {
    $client = new Client();
    $client->setAuth('pk1_key', 'sk1_secret');

    $pricingService = $client->pricing();
    $authService = $client->auth();
    $domainService = $client->domains();
    $dnsService = $client->dns('example.com');
    $sslService = $client->ssl('example.com');

    // All services should share the same config instance
    $clientConfig = $client->getConfig();

    // Use reflection to access protected config property
    $services = [$pricingService, $authService, $domainService, $dnsService, $sslService];

    foreach ($services as $service) {
        $reflection = new ReflectionClass($service);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        /** @var Config $serviceConfig */
        $serviceConfig = $configProperty->getValue($service);

        expect($serviceConfig)->toBe($clientConfig);
        expect($serviceConfig->hasAuth())->toBeTrue();
    }
});
