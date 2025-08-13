<?php

declare(strict_types=1);

use Porkbun\Exception\RuntimeException;
use Porkbun\Middleware\RateLimitMiddleware;
use Porkbun\Request\GetPricingRequest;
use Psr\Http\Message\RequestInterface;

test('rate limit middleware allows requests within limit', function (): void {
    $middleware = new RateLimitMiddleware(5, 60); // 5 requests per minute
    $request = new GetPricingRequest();
    $mock = Mockery::mock(RequestInterface::class);

    // Should allow first request
    $result = $middleware->beforeRequest($request, $mock);
    expect($result)->toBe($mock);
    expect($middleware->getRemainingRequests())->toBe(4);

    // Should allow second request
    $result = $middleware->beforeRequest($request, $mock);
    expect($result)->toBe($mock);
    expect($middleware->getRemainingRequests())->toBe(3);
});

test('rate limit middleware blocks requests over limit', function (): void {
    $middleware = new RateLimitMiddleware(2, 60); // 2 requests per minute
    $request = new GetPricingRequest();
    $mock = Mockery::mock(RequestInterface::class);

    // Allow first two requests
    $middleware->beforeRequest($request, $mock);
    $middleware->beforeRequest($request, $mock);

    expect($middleware->getRemainingRequests())->toBe(0);

    // Third request should be blocked
    expect(fn (): RequestInterface => $middleware->beforeRequest($request, $mock))
        ->toThrow(RuntimeException::class, 'Rate limit exceeded');
});

test('rate limit middleware cleans up old requests', function (): void {
    $middleware = new RateLimitMiddleware(2, 1); // 2 requests per second
    $request = new GetPricingRequest();
    $mock = Mockery::mock(RequestInterface::class);

    // Use up the limit
    $middleware->beforeRequest($request, $mock);
    $middleware->beforeRequest($request, $mock);

    expect($middleware->getRemainingRequests())->toBe(0);

    // Wait for window to pass (simulate)
    sleep(2);

    // Should allow new requests after window passes
    $result = $middleware->beforeRequest($request, $mock);
    expect($result)->toBe($mock);
    expect($middleware->getRemainingRequests())->toBe(1);
});

test('rate limit middleware returns correct window seconds', function (): void {
    $middleware = new RateLimitMiddleware(10, 30);
    expect($middleware->getWindowSeconds())->toBe(30);
});

test('rate limit middleware handles custom limits', function (): void {
    $middleware = new RateLimitMiddleware(100, 3600); // 100 requests per hour
    $request = new GetPricingRequest();
    $mock = Mockery::mock(RequestInterface::class);

    // Should start with full limit
    expect($middleware->getRemainingRequests())->toBe(100);

    // After one request
    $middleware->beforeRequest($request, $mock);
    expect($middleware->getRemainingRequests())->toBe(99);
});
