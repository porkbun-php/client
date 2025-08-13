<?php

declare(strict_types=1);

use Porkbun\Config;
use Porkbun\Service\PricingService;
use Psr\Http\Client\ClientInterface;

test('pricing service does not require auth', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();

    $service = new PricingService($mock, $config);

    // Use reflection to access protected method
    $reflection = new ReflectionClass($service);
    $reflectionMethod = $reflection->getMethod('requiresAuth');
    $reflectionMethod->setAccessible(true);

    expect($reflectionMethod->invoke($service))->toBeFalse();
});

test('pricing service can get pricing', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();

    $responseData = [
        'status' => 'SUCCESS',
        'pricing' => [
            'com' => [
                'registration' => '8.68',
                'renewal' => '8.68',
            ],
        ],
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new PricingService($mock, $config);
    $result = $service->getPricing();

    expect($result)->toBe($responseData);
});
