<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Porkbun\HttpClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses()->afterEach(function (): void {
    Mockery::close();
})->in('Unit', 'Integration');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function createMockResponse(string $jsonResponse, int $statusCode = 200): ResponseInterface
{
    $factory = new Psr17Factory();
    $stream = $factory->createStream($jsonResponse);

    return $factory->createResponse($statusCode)
        ->withHeader('Content-Type', 'application/json')
        ->withBody($stream);
}

/**
 * Create a mock HTTP client that returns sequential responses.
 *
 * @param array<array{body?: array<string, mixed>, httpStatus?: int}|array<string, mixed>> $responses
 *        Each response can be:
 *        - A simple array (will be encoded as JSON body, 200 status)
 *        - An array with 'body' key (the array to encode) and optional 'httpStatus' (HTTP status code)
 */
function createMockHttpClient(array $responses): MockInterface&ClientInterface
{
    /** @var MockInterface&ClientInterface $mock */
    $mock = Mockery::mock(ClientInterface::class);

    $responseObjects = [];
    foreach ($responses as $response) {
        // If 'body' key exists, it's the explicit format
        if (isset($response['body'])) {
            $body = $response['body'];
            /** @var int $statusCode */
            $statusCode = $response['httpStatus'] ?? 200;
        } else {
            // Otherwise the whole array is the body
            $body = $response;
            $statusCode = 200;
        }

        $jsonBody = json_encode($body, JSON_THROW_ON_ERROR);
        $responseObjects[] = createMockResponse($jsonBody, $statusCode);
    }

    if ($responseObjects !== []) {
        $mock->shouldReceive('sendRequest')
            ->times(count($responseObjects))
            ->andReturn(...$responseObjects);
    }

    return $mock;
}

function createHttpClient(
    MockInterface&ClientInterface $mockClient,
    ?string $apiKey = null,
    ?string $secretKey = null,
    string $baseUrl = 'https://api.porkbun.com/api/json/v3',
): HttpClient {
    $factory = new Psr17Factory();

    return new HttpClient(
        $mockClient,
        $factory,
        $factory,
        $baseUrl,
        $apiKey,
        $secretKey,
    );
}
