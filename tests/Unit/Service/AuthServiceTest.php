<?php

declare(strict_types=1);

use Porkbun\Config;
use Porkbun\Service\AuthService;
use Psr\Http\Client\ClientInterface;

test('auth service requires auth', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();

    $service = new AuthService($mock, $config);

    // Use reflection to access protected method
    $reflection = new ReflectionClass($service);
    $reflectionMethod = $reflection->getMethod('requiresAuth');
    $reflectionMethod->setAccessible(true);

    expect($reflectionMethod->invoke($service))->toBeTrue();
});

test('auth service can ping', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = [
        'status' => 'SUCCESS',
        'yourIp' => '192.168.1.1',
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new AuthService($mock, $config);
    $result = $service->ping();

    expect($result)->toBe($responseData);
});
