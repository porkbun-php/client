<?php

declare(strict_types=1);

use Porkbun\Config;
use Porkbun\Service\SslService;
use Psr\Http\Client\ClientInterface;

test('ssl service requires auth', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();

    $service = new SslService($mock, $config, 'example.com');

    // Use reflection to access protected method
    $reflection = new ReflectionClass($service);
    $reflectionMethod = $reflection->getMethod('requiresAuth');
    $reflectionMethod->setAccessible(true);

    expect($reflectionMethod->invoke($service))->toBeTrue();
});

test('ssl service can retrieve certificate', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = [
        'status' => 'SUCCESS',
        'certificatechain' => 'certificate-content',
        'privatekey' => 'private-key-content',
        'publickey' => 'public-key-content',
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new SslService($mock, $config, 'example.com');
    $result = $service->retrieve();

    expect($result)->toBe($responseData);
});
