<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use Porkbun\Exception\ApiException;
use Porkbun\Exception\AuthenticationException;
use Porkbun\HttpClient;
use Psr\Http\Client\ClientInterface;

test('http client throws on non-json response', function (): void {
    $factory = new Psr17Factory();
    $response = $factory->createResponse(200)
        ->withBody($factory->createStream('Not JSON'));

    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->andReturn($response);

    $httpClient = new HttpClient($mock, $factory, $factory);

    expect(fn (): array => $httpClient->post('/test'))
        ->toThrow(ApiException::class, 'Invalid API response: expected JSON');
});

test('http client throws on non-object json response', function (): void {
    $factory = new Psr17Factory();
    // Use valid JSON array that decodes to non-array (null)
    $response = $factory->createResponse(200)
        ->withBody($factory->createStream('[1, 2, 3]'));

    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->andReturn($response);

    $httpClient = new HttpClient($mock, $factory, $factory);

    // Array is valid, but missing status field
    expect(fn (): array => $httpClient->post('/test'))
        ->toThrow(ApiException::class, 'Invalid API response format: missing status field');
});

test('http client throws on invalid json', function (): void {
    $factory = new Psr17Factory();
    $response = $factory->createResponse(200)
        ->withBody($factory->createStream('{invalid json}'));

    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->andReturn($response);

    $httpClient = new HttpClient($mock, $factory, $factory);

    expect(fn (): array => $httpClient->post('/test'))
        ->toThrow(ApiException::class, 'Invalid JSON response');
});

test('http client throws on missing status field', function (): void {
    $factory = new Psr17Factory();
    $response = $factory->createResponse(200)
        ->withBody($factory->createStream('{"data": "test"}'));

    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->andReturn($response);

    $httpClient = new HttpClient($mock, $factory, $factory);

    expect(fn (): array => $httpClient->post('/test'))
        ->toThrow(ApiException::class, 'Invalid API response format: missing status field');
});

test('http client throws on error status', function (): void {
    $factory = new Psr17Factory();
    $response = $factory->createResponse(200)
        ->withBody($factory->createStream('{"status": "ERROR", "message": "Domain not found"}'));

    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->andReturn($response);

    $httpClient = new HttpClient($mock, $factory, $factory);

    expect(fn (): array => $httpClient->post('/test'))
        ->toThrow(ApiException::class, 'Domain not found');
});

test('http client extracts error from 4xx json response', function (): void {
    $factory = new Psr17Factory();
    $response = $factory->createResponse(400)
        ->withBody($factory->createStream('{"status": "ERROR", "message": "Bad request"}'));

    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->andReturn($response);

    $httpClient = new HttpClient($mock, $factory, $factory);

    expect(fn (): array => $httpClient->post('/test'))
        ->toThrow(ApiException::class, 'Bad request');
});

test('http client extracts error field from response', function (): void {
    $factory = new Psr17Factory();
    $response = $factory->createResponse(400)
        ->withBody($factory->createStream('{"error": "Something went wrong"}'));

    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->andReturn($response);

    $httpClient = new HttpClient($mock, $factory, $factory);

    expect(fn (): array => $httpClient->post('/test'))
        ->toThrow(ApiException::class, 'Something went wrong');
});

test('http client uses plain text error body', function (): void {
    $factory = new Psr17Factory();
    $response = $factory->createResponse(500)
        ->withBody($factory->createStream('Internal Server Error'));

    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->andReturn($response);

    $httpClient = new HttpClient($mock, $factory, $factory);

    expect(fn (): array => $httpClient->post('/test'))
        ->toThrow(ApiException::class, 'Internal Server Error');
});

test('http client uses generic message for empty error body', function (): void {
    $factory = new Psr17Factory();
    $response = $factory->createResponse(404)
        ->withBody($factory->createStream(''));

    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->andReturn($response);

    $httpClient = new HttpClient($mock, $factory, $factory);

    expect(fn (): array => $httpClient->post('/test'))
        ->toThrow(ApiException::class, 'HTTP 404 error');
});

test('http client handles error status without message', function (): void {
    $factory = new Psr17Factory();
    $response = $factory->createResponse(200)
        ->withBody($factory->createStream('{"status": "ERROR"}'));

    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->andReturn($response);

    $httpClient = new HttpClient($mock, $factory, $factory);

    expect(fn (): array => $httpClient->post('/test'))
        ->toThrow(ApiException::class, 'Unknown API error');
});

test('http client get method with query params', function (): void {
    $factory = new Psr17Factory();
    $response = $factory->createResponse(200)
        ->withBody($factory->createStream('{"status": "SUCCESS", "data": "test"}'));

    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')
        ->once()
        ->withArgs(fn ($request): bool => str_contains((string) $request->getUri(), 'foo=bar'))
        ->andReturn($response);

    $httpClient = new HttpClient($mock, $factory, $factory);

    $result = $httpClient->get('/test', ['foo' => 'bar']);

    expect($result['status'])->toBe('SUCCESS');
});

test('http client hasAuthentication checks credentials', function (): void {
    $factory = new Psr17Factory();
    $mock = Mockery::mock(ClientInterface::class);

    $withAuth = new HttpClient($mock, $factory, $factory, 'https://api.example.com', 'key', 'secret');
    $withoutAuth = new HttpClient($mock, $factory, $factory);
    $partialAuth = new HttpClient($mock, $factory, $factory, 'https://api.example.com', 'key');

    expect($withAuth->hasAuthentication())->toBeTrue()
        ->and($withoutAuth->hasAuthentication())->toBeFalse()
        ->and($partialAuth->hasAuthentication())->toBeFalse();
});

test('http client baseUrl returns base url', function (): void {
    $factory = new Psr17Factory();
    $mock = Mockery::mock(ClientInterface::class);

    $httpClient = new HttpClient($mock, $factory, $factory, 'https://custom.api.com');

    expect($httpClient->baseUrl)->toBe('https://custom.api.com');
});

test('http client reads error code from 4xx response', function (): void {
    $factory = new Psr17Factory();
    $response = $factory->createResponse(400)
        ->withBody($factory->createStream('{"status":"ERROR","message":"Invalid domain: example.com is not in the account sandbox-user.","code":"INVALID_DOMAIN","account":"sandbox-user","next_action":{"type":"fix_request","hint":"The domain name is invalid. Check the formatting.","retryable":false},"requestId":"00000000-0000-0000-0000-000000000000","sandbox":true}'));

    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->andReturn($response);

    $httpClient = new HttpClient($mock, $factory, $factory);

    $e = catchPorkbunException(fn (): array => $httpClient->post('/test'));

    expect($e)->toBeInstanceOf(ApiException::class)
        ->and($e->getErrorCode())->toBe('INVALID_DOMAIN')
        ->and($e->getMessage())->toBe('Invalid domain: example.com is not in the account sandbox-user.');
});

test('http client reads error code from authentication error', function (): void {
    $factory = new Psr17Factory();
    $response = $factory->createResponse(400)
        ->withBody($factory->createStream('{"status":"ERROR","message":"Invalid API key.","code":"INVALID_API_KEYS","next_action":{"type":"authenticate","hint":"Your API key or secret is invalid. Verify them, or create a new key.","retryable":false,"url":"https:\\/\\/porkbun.com\\/account\\/api"},"requestId":"00000000-0000-0000-0000-000000000000"}'));

    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->andReturn($response);

    $httpClient = new HttpClient($mock, $factory, $factory);

    $e = catchPorkbunException(fn (): array => $httpClient->post('/test'));

    expect($e)->toBeInstanceOf(AuthenticationException::class)
        ->and($e->getErrorCode())->toBe('INVALID_API_KEYS');
});

test('http client reads error code from 403 response', function (): void {
    $factory = new Psr17Factory();
    $response = $factory->createResponse(403)
        ->withBody($factory->createStream('{"status":"ERROR","message":"Invalid API key.","code":"INVALID_API_KEYS","requestId":"00000000-0000-0000-0000-000000000000"}'));

    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->andReturn($response);

    $httpClient = new HttpClient($mock, $factory, $factory);

    $e = catchPorkbunException(fn (): array => $httpClient->post('/test'));

    expect($e)->toBeInstanceOf(AuthenticationException::class)
        ->and($e->getErrorCode())->toBe('INVALID_API_KEYS');
});

test('http client reads error code from error status in 200 response', function (): void {
    $factory = new Psr17Factory();
    $response = $factory->createResponse(200)
        ->withBody($factory->createStream('{"status":"ERROR","message":"Cost must be a valid integer.","code":"INVALID_INPUT","requestId":"00000000-0000-0000-0000-000000000000","sandbox":true}'));

    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->andReturn($response);

    $httpClient = new HttpClient($mock, $factory, $factory);

    $e = catchPorkbunException(fn (): array => $httpClient->post('/test'));

    expect($e)->toBeInstanceOf(ApiException::class)
        ->and($e->getErrorCode())->toBe('INVALID_INPUT');
});

test('http client leaves error code null when body has none', function (): void {
    $factory = new Psr17Factory();
    $cases = [
        [400, '{"status": "ERROR", "message": "Bad request"}'],
        [400, '{"status": "ERROR", "message": "Bad request", "code": 123}'],
        [500, 'Internal Server Error'],
        [502, '{invalid json}'],
    ];

    foreach ($cases as [$status, $body]) {
        $response = $factory->createResponse($status)->withBody($factory->createStream($body));

        $mock = Mockery::mock(ClientInterface::class);
        $mock->shouldReceive('sendRequest')->andReturn($response);

        $httpClient = new HttpClient($mock, $factory, $factory);

        $e = catchPorkbunException(fn (): array => $httpClient->post('/test'));

        expect($e)->toBeInstanceOf(ApiException::class)
            ->and($e->getErrorCode())->toBeNull($body);
    }
});
