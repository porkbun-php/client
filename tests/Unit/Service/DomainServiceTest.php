<?php

declare(strict_types=1);

use Porkbun\Config;
use Porkbun\Service\DomainService;
use Psr\Http\Client\ClientInterface;

test('domain service requires auth', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();

    $service = new DomainService($mock, $config);

    // Use reflection to access protected method
    $reflection = new ReflectionClass($service);
    $reflectionMethod = $reflection->getMethod('requiresAuth');
    $reflectionMethod->setAccessible(true);

    expect($reflectionMethod->invoke($service))->toBeTrue();
});

test('domain service can list all domains', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = [
        'status' => 'SUCCESS',
        'domains' => [
            ['domain' => 'example.com', 'status' => 'ACTIVE'],
        ],
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DomainService($mock, $config);
    $result = $service->listAll();

    expect($result)->toBe($responseData);
});

test('domain service can list all domains with parameters', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = [
        'status' => 'SUCCESS',
        'domains' => [],
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DomainService($mock, $config);
    $result = $service->listAll(10, true);

    expect($result)->toBe($responseData);
});

test('domain service can check domain', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = [
        'status' => 'SUCCESS',
        'available' => true,
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DomainService($mock, $config);
    $result = $service->checkDomain('example.com');

    expect($result)->toBe($responseData);
});

test('domain service can update nameservers', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = ['status' => 'SUCCESS'];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DomainService($mock, $config);
    $service->updateNs('example.com', ['ns1.example.com', 'ns2.example.com']);

    expect(true)->toBeTrue(); // No exception thrown
});

test('domain service can get nameservers', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = [
        'status' => 'SUCCESS',
        'ns' => ['ns1.example.com', 'ns2.example.com'],
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DomainService($mock, $config);
    $result = $service->getNs('example.com');

    expect($result)->toBe($responseData);
});

test('domain service can add url forward', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = ['status' => 'SUCCESS'];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DomainService($mock, $config);
    $params = [
        'subdomain' => 'blog',
        'location' => 'https://new-blog.com',
        'type' => 'permanent',
    ];
    $service->addUrlForward('example.com', $params);

    expect(true)->toBeTrue(); // No exception thrown
});

test('domain service can get url forwarding', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = [
        'status' => 'SUCCESS',
        'forwards' => [],
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DomainService($mock, $config);
    $result = $service->getUrlForwarding('example.com');

    expect($result)->toBe($responseData);
});

test('domain service can delete url forward', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = ['status' => 'SUCCESS'];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DomainService($mock, $config);
    $service->deleteUrlForward('example.com', 123);

    expect(true)->toBeTrue(); // No exception thrown
});

test('domain service can create glue record', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = ['status' => 'SUCCESS'];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DomainService($mock, $config);
    $service->createGlue('example.com', 'ns1', ['192.168.1.1']);

    expect(true)->toBeTrue(); // No exception thrown
});

test('domain service can update glue record', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = ['status' => 'SUCCESS'];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DomainService($mock, $config);
    $service->updateGlue('example.com', 'ns1', ['192.168.1.2']);

    expect(true)->toBeTrue(); // No exception thrown
});

test('domain service can delete glue record', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = ['status' => 'SUCCESS'];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DomainService($mock, $config);
    $service->deleteGlue('example.com', 'ns1');

    expect(true)->toBeTrue(); // No exception thrown
});

test('domain service can get glue records', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = [
        'status' => 'SUCCESS',
        'glue' => [],
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DomainService($mock, $config);
    $result = $service->getGlue('example.com');

    expect($result)->toBe($responseData);
});
