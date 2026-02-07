<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use Porkbun\Exception\ApiException;
use Porkbun\Exception\AuthenticationException;
use Porkbun\Exception\NetworkException;

test('api exception stores request and response', function (): void {
    $factory = new Psr17Factory();
    $request = $factory->createRequest('POST', 'https://api.example.com/test');
    $response = $factory->createResponse(400);

    $exception = new ApiException('Test error', 400, null, $request, $response);

    expect($exception->getRequest())->toBe($request)
        ->and($exception->getResponse())->toBe($response)
        ->and($exception->getStatusCode())->toBe(400)
        ->and($exception->hasRequest())->toBeTrue()
        ->and($exception->hasResponse())->toBeTrue();
});

test('api exception without request and response', function (): void {
    $exception = new ApiException('Test error', 500);

    expect($exception->getRequest())->toBeNull()
        ->and($exception->getResponse())->toBeNull()
        ->and($exception->hasRequest())->toBeFalse()
        ->and($exception->hasResponse())->toBeFalse();
});

test('authentication exception stores request and response', function (): void {
    $factory = new Psr17Factory();
    $request = $factory->createRequest('POST', 'https://api.example.com/test');
    $response = $factory->createResponse(403);

    $exception = new AuthenticationException('Auth failed', 403, null, $request, $response);

    expect($exception->getRequest())->toBe($request)
        ->and($exception->getResponse())->toBe($response)
        ->and($exception->getStatusCode())->toBe(403)
        ->and($exception->hasRequest())->toBeTrue()
        ->and($exception->hasResponse())->toBeTrue();
});

test('authentication exception uses defaults', function (): void {
    $exception = new AuthenticationException();

    expect($exception->getMessage())->toBe('Authentication required or invalid')
        ->and($exception->getStatusCode())->toBe(403);
});

test('network exception stores request', function (): void {
    $factory = new Psr17Factory();
    $request = $factory->createRequest('POST', 'https://api.example.com/test');

    $exception = new NetworkException('Connection failed', 0, null, $request);

    expect($exception->getRequest())->toBe($request)
        ->and($exception->getStatusCode())->toBe(0)
        ->and($exception->hasRequest())->toBeTrue();
});

test('network exception uses defaults', function (): void {
    $exception = new NetworkException();

    expect($exception->getMessage())->toBe('Network or HTTP client error')
        ->and($exception->getStatusCode())->toBe(0)
        ->and($exception->hasRequest())->toBeFalse();
});

test('exceptions preserve previous exception', function (): void {
    $previous = new RuntimeException('Original error');

    $apiException = new ApiException('API error', 500, $previous);
    $authException = new AuthenticationException('Auth error', 403, $previous);
    $networkException = new NetworkException('Network error', 0, $previous);

    expect($apiException->getPrevious())->toBe($previous)
        ->and($authException->getPrevious())->toBe($previous)
        ->and($networkException->getPrevious())->toBe($previous);
});
