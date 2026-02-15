<?php

declare(strict_types=1);

use Porkbun\Exception\ApiException;
use Porkbun\Exception\AuthenticationException;
use Porkbun\Exception\NetworkException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

test('http client can make POST request without auth', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'pricing' => []],
    ]);

    $httpClient = createHttpClient($mockClient);
    $result = $httpClient->post('/pricing/get');

    expect($result)->toBeArray()
        ->and($result['status'])->toBe('SUCCESS');
});

test('http client adds authentication to POST request', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            $body = json_decode((string) $request->getBody(), true);

            expect($body)->toHaveKey('apikey', 'pk1_key')
                ->and($body)->toHaveKey('secretapikey', 'sk1_secret');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $result = $httpClient->post('/ping');

    expect($result['status'])->toBe('SUCCESS');
});

test('http client sets correct headers', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect($request->getHeaderLine('Content-Type'))->toBe('application/json')
                ->and($request->getHeaderLine('User-Agent'))->toStartWith('porkbun-php-api/');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock);
    $httpClient->post('/ping');
});

test('http client throws ApiException on error response', function (): void {
    $mockClient = createMockHttpClient([
        ['body' => ['status' => 'ERROR', 'message' => 'Invalid domain'], 'httpStatus' => 200],
    ]);

    $httpClient = createHttpClient($mockClient);

    expect(fn (): array => $httpClient->post('/domain/check'))
        ->toThrow(ApiException::class, 'Invalid domain');
});

test('http client throws AuthenticationException on 403', function (): void {
    $mockClient = createMockHttpClient([
        ['body' => ['status' => 'ERROR', 'message' => 'Invalid API key'], 'httpStatus' => 403],
    ]);

    $httpClient = createHttpClient($mockClient);

    expect(fn (): array => $httpClient->post('/ping'))
        ->toThrow(AuthenticationException::class);
});

test('http client throws NetworkException on client error', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    // Create an anonymous class implementing ClientExceptionInterface
    $exception = new class ('Connection failed') extends Exception implements ClientExceptionInterface {};

    $mock->shouldReceive('sendRequest')
        ->once()
        ->andThrow($exception);

    $httpClient = createHttpClient($mock);

    expect(fn (): array => $httpClient->post('/ping'))
        ->toThrow(NetworkException::class, 'Connection failed');
});

test('http client stores last response', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS'],
    ]);

    $httpClient = createHttpClient($mockClient);

    expect($httpClient->getLastResponse())->toBeNull();

    $httpClient->post('/ping');

    expect($httpClient->getLastResponse())->not->toBeNull();
});
