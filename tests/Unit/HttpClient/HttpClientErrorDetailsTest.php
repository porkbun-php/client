<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use Porkbun\Exception\ApiException;
use Porkbun\Exception\AuthenticationException;
use Porkbun\Exception\PorkbunApiException;
use Porkbun\Exception\RateLimitException;
use Porkbun\HttpClient;
use Psr\Http\Client\ClientInterface;

/**
 * @param string|array<string, mixed> $body
 * @param array<string, string> $headers
 */
function catchApiError(int $status, string|array $body, array $headers = []): PorkbunApiException
{
    $factory = new Psr17Factory();
    $body = is_array($body) ? json_encode($body, JSON_THROW_ON_ERROR) : $body;
    $response = $factory->createResponse($status)->withBody($factory->createStream($body));

    foreach ($headers as $name => $value) {
        $response = $response->withHeader($name, $value);
    }

    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->andReturn($response);

    try {
        new HttpClient($mock, $factory, $factory)->post('/test');
    } catch (PorkbunApiException $e) {
        return $e;
    }

    throw new RuntimeException('Expected an API exception');
}

test('api error exposes code, next action and request id', function (): void {
    $exception = catchApiError(400, [
        'status' => 'ERROR',
        'message' => 'Invalid domain: example.com is not in the account example-user.',
        'code' => 'INVALID_DOMAIN',
        'account' => 'example-user',
        'next_action' => ['type' => 'fix_request', 'hint' => 'Check the formatting.', 'retryable' => false],
        'requestId' => '00000000-0000-0000-0000-000000000001',
    ]);

    expect($exception)->toBeInstanceOf(ApiException::class)
        ->and($exception->getMessage())->toBe('Invalid domain: example.com is not in the account example-user.')
        ->and($exception->getStatusCode())->toBe(400)
        ->and($exception->getErrorCode())->toBe('INVALID_DOMAIN')
        ->and($exception->getNextAction()?->type)->toBe('fix_request')
        ->and($exception->getNextAction()?->hint)->toBe('Check the formatting.')
        ->and($exception->getNextAction()?->retryable)->toBeFalse()
        ->and($exception->getRequestId())->toBe('00000000-0000-0000-0000-000000000001');
});

test('invalid api keys error is an authentication error', function (): void {
    $exception = catchApiError(400, [
        'status' => 'ERROR',
        'message' => 'Invalid API key. (002)',
        'code' => 'INVALID_API_KEYS_002',
        'next_action' => ['type' => 'authenticate', 'retryable' => false, 'url' => 'https://porkbun.com/account/api'],
    ]);

    expect($exception)->toBeInstanceOf(AuthenticationException::class)
        ->and($exception->getErrorCode())->toBe('INVALID_API_KEYS_002')
        ->and($exception->getNextAction()?->url)->toBe('https://porkbun.com/account/api');
});

test('403 keeps the api message and code', function (): void {
    $exception = catchApiError(403, [
        'status' => 'ERROR',
        'message' => 'This API key does not allow requests from 192.0.2.1.',
        'code' => 'IP_NOT_ALLOWED',
    ]);

    expect($exception)->toBeInstanceOf(AuthenticationException::class)
        ->and($exception->getMessage())->toBe('This API key does not allow requests from 192.0.2.1.')
        ->and($exception->getErrorCode())->toBe('IP_NOT_ALLOWED');
});

test('403 without a body uses the generic message', function (): void {
    $exception = catchApiError(403, '');

    expect($exception)->toBeInstanceOf(AuthenticationException::class)
        ->and($exception->getMessage())->toBe('Authentication required or invalid');
});

test('error code decides over message text', function (): void {
    $exception = catchApiError(400, [
        'status' => 'ERROR',
        'message' => 'Authentication field is missing',
        'code' => 'INVALID_INPUT',
    ]);

    expect($exception)->toBeInstanceOf(ApiException::class)
        ->and($exception)->not->toBeInstanceOf(AuthenticationException::class);
});

test('429 throws rate limit exception with retry-after header', function (): void {
    $exception = catchApiError(429, [
        'status' => 'ERROR',
        'message' => 'Rate limit exceeded. Please try again later.',
        'code' => 'RATE_LIMIT_EXCEEDED',
    ], ['Retry-After' => '30']);

    expect($exception)->toBeInstanceOf(RateLimitException::class)
        ->and($exception)->toBeInstanceOf(ApiException::class)
        ->and($exception->getErrorCode())->toBe('RATE_LIMIT_EXCEEDED');

    assert($exception instanceof RateLimitException);
    expect($exception->getRetryAfter())->toBe(30);
});

test('rate limit falls back to ttlRemaining', function (): void {
    $exception = catchApiError(429, [
        'status' => 'ERROR',
        'message' => 'Rate limit exceeded.',
        'code' => 'RATE_LIMIT_EXCEEDED',
        'ttlRemaining' => 12,
    ]);

    assert($exception instanceof RateLimitException);
    expect($exception->getRetryAfter())->toBe(12);
});

test('html error page is not used as the message', function (): void {
    $html = '<html><head><title>503 Service Temporarily Unavailable</title></head></html>';
    $exception = catchApiError(503, $html, ['X-Request-Id' => '00000000-0000-0000-0000-000000000002']);

    expect($exception)->toBeInstanceOf(ApiException::class)
        ->and($exception->getMessage())->toBe('HTTP 503 error')
        ->and($exception->getErrorCode())->toBeNull()
        ->and($exception->getRequestId())->toBe('00000000-0000-0000-0000-000000000002')
        ->and((string) $exception->getResponse()?->getBody())->toBe($html);
});

test('error status in a 200 response exposes the code', function (): void {
    $exception = catchApiError(200, [
        'status' => 'ERROR',
        'message' => 'Domain is not available.',
        'code' => 'DOMAIN_NOT_AVAILABLE',
    ]);

    expect($exception)->toBeInstanceOf(ApiException::class)
        ->and($exception->getStatusCode())->toBe(200)
        ->and($exception->getErrorCode())->toBe('DOMAIN_NOT_AVAILABLE');
});
