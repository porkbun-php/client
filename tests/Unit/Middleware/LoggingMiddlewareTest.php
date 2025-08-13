<?php

declare(strict_types=1);

use Porkbun\Middleware\LoggingMiddleware;
use Porkbun\Request\GetPricingRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

test('logging middleware logs before request', function (): void {
    $mock = Mockery::mock(LoggerInterface::class);
    $mock->shouldReceive('info')
        ->once()
        ->with('API Request', Mockery::on(fn ($context): bool => $context['method'] === 'POST'
            && $context['endpoint'] === '/pricing/get'
            && isset($context['url'])));

    $middleware = new LoggingMiddleware($mock);
    $request = new GetPricingRequest();

    $uri = Mockery::mock(UriInterface::class);
    $uri->shouldReceive('__toString')->andReturn('https://api.porkbun.com/api/json/v3/pricing/get');

    $httpRequest = Mockery::mock(RequestInterface::class);
    $httpRequest->shouldReceive('getUri')->andReturn($uri);

    $result = $middleware->beforeRequest($request, $httpRequest);
    expect($result)->toBe($httpRequest);
});

test('logging middleware logs after response', function (): void {
    $mock = Mockery::mock(LoggerInterface::class);
    $mock->shouldReceive('info')
        ->once()
        ->with('API Response', Mockery::on(fn (array $context): bool => $context['endpoint'] === '/pricing/get'
            && $context['status_code'] === 200
            && $context['success'] === true));

    $middleware = new LoggingMiddleware($mock);
    $request = new GetPricingRequest();

    $httpResponse = Mockery::mock(ResponseInterface::class);
    $httpResponse->shouldReceive('getStatusCode')->andReturn(200);

    $responseData = ['status' => 'SUCCESS', 'pricing' => []];

    $result = $middleware->afterResponse($request, $httpResponse, $responseData);
    expect($result)->toBe($responseData);
});

test('logging middleware logs errors', function (): void {
    $mock = Mockery::mock(LoggerInterface::class);
    $mock->shouldReceive('error')
        ->once()
        ->with('API Error', Mockery::on(fn (array $context): bool => $context['endpoint'] === '/pricing/get'
            && $context['error'] === 'Test error'
            && $context['exception'] === 'Exception'));

    $middleware = new LoggingMiddleware($mock);
    $request = new GetPricingRequest();

    $error = new Exception('Test error');

    $result = $middleware->onError($request, $error);
    expect($result)->toBe($error);
});

test('logging middleware uses null logger by default', function (): void {
    $middleware = new LoggingMiddleware();
    $request = new GetPricingRequest();

    $mock = Mockery::mock(UriInterface::class);
    $mock->shouldReceive('__toString')->andReturn('test-url');

    $httpRequest = Mockery::mock(RequestInterface::class);
    $httpRequest->shouldReceive('getUri')->andReturn($mock);

    // Should not throw any errors with null logger
    $result = $middleware->beforeRequest($request, $httpRequest);
    expect($result)->toBe($httpRequest);
});
