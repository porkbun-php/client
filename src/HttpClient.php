<?php

declare(strict_types=1);

namespace Porkbun;

use Composer\InstalledVersions;
use JsonException;
use OutOfBoundsException;
use Porkbun\Enum\Endpoint;
use Porkbun\Exception\ApiException;
use Porkbun\Exception\AuthenticationException;
use Porkbun\Exception\InvalidArgumentException;
use Porkbun\Exception\NetworkException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * @internal This class is not part of the public API and may change without notice.
 */
final class HttpClient
{
    private const string CONTENT_TYPE = 'application/json';

    public private(set) ?ResponseInterface $lastResponse = null;

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        public readonly string $baseUrl = Endpoint::DEFAULT->value,
        private readonly ?string $apiKey = null,
        private readonly ?string $secretKey = null,
    ) {
    }

    public function hasAuthentication(): bool
    {
        return $this->apiKey !== null && $this->secretKey !== null;
    }

    /**
     * @throws ApiException
     * @throws AuthenticationException
     * @throws NetworkException
     */
    public function post(string $path, array $data = []): array
    {
        $url = $this->baseUrl . $path;

        $bodyData = $this->addAuthenticationToData($data);
        $body = $this->encodeJson($bodyData);

        $request = $this->requestFactory->createRequest('POST', $url)
            ->withHeader('Content-Type', self::CONTENT_TYPE)
            ->withHeader('User-Agent', $this->getUserAgent())
            ->withBody($this->streamFactory->createStream($body));

        try {
            $this->lastResponse = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new NetworkException(
                sprintf('HTTP POST request to %s failed: %s', $url, $e->getMessage()),
                0,
                $e,
                $request
            );
        }

        return $this->parseResponse($this->lastResponse, $request);
    }

    /**
     * @throws ApiException
     * @throws NetworkException
     */
    public function get(string $path, array $params = []): array
    {
        $url = $this->baseUrl . $path;

        if ($params !== []) {
            $url .= '?' . http_build_query($params);
        }

        $request = $this->requestFactory->createRequest('GET', $url)
            ->withHeader('Content-Type', self::CONTENT_TYPE)
            ->withHeader('User-Agent', $this->getUserAgent());

        try {
            $this->lastResponse = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new NetworkException(
                sprintf('HTTP GET request to %s failed: %s', $url, $e->getMessage()),
                0,
                $e,
                $request
            );
        }

        return $this->parseResponse($this->lastResponse, $request);
    }

    private function addAuthenticationToData(array $data): array
    {
        if ($this->apiKey !== null && $this->secretKey !== null) {
            $data['apikey'] = $this->apiKey;
            $data['secretapikey'] = $this->secretKey;
        }

        return $data;
    }

    private function encodeJson(array $data): string
    {
        try {
            return json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException(
                'Invalid request parameters: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function parseResponse(ResponseInterface $response, RequestInterface $request): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($statusCode === 403) {
            throw new AuthenticationException(
                'Authentication required or invalid',
                403,
                null,
                $request,
                $response
            );
        }

        if ($statusCode >= 400) {
            $message = $this->extractErrorMessage($body) ?? "HTTP {$statusCode} error";

            if ($this->isAuthenticationError($message)) {
                throw new AuthenticationException($message, $statusCode, null, $request, $response);
            }

            throw new ApiException($message, $statusCode, null, $request, $response);
        }

        $content = $this->decodeJson($body, $statusCode, $request, $response);
        $this->validateSuccessResponse($content, $request, $response);

        return $content;
    }

    private function decodeJson(string $body, int $statusCode, RequestInterface $request, ResponseInterface $response): array
    {
        if (!$this->looksLikeJson($body)) {
            throw new ApiException('Invalid API response: expected JSON', $statusCode, null, $request, $response);
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($decoded)) {
                throw new ApiException('Invalid API response: expected JSON object or array', $statusCode, null, $request, $response);
            }

            return $decoded;
        } catch (JsonException $e) {
            throw new ApiException('Invalid JSON response: ' . $e->getMessage(), $statusCode, null, $request, $response);
        }
    }

    private function validateSuccessResponse(array $data, RequestInterface $request, ResponseInterface $response): void
    {
        if (!isset($data['status'])) {
            throw new ApiException('Invalid API response format: missing status field', $response->getStatusCode(), null, $request, $response);
        }

        if ($data['status'] !== 'SUCCESS') {
            $message = is_string($data['message'] ?? null) ? $data['message'] : 'Unknown API error';

            if ($this->isAuthenticationError($message)) {
                throw new AuthenticationException($message, $response->getStatusCode(), null, $request, $response);
            }

            throw new ApiException($message, $response->getStatusCode(), null, $request, $response);
        }
    }

    private function extractErrorMessage(string $body): ?string
    {
        if (!$this->looksLikeJson($body)) {
            return $body !== '' ? $body : null;
        }

        try {
            $content = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            if (is_array($content)) {
                if (($content['status'] ?? '') === 'ERROR' && is_string($content['message'] ?? null)) {
                    return $content['message'];
                }

                if (is_string($content['message'] ?? null)) {
                    return $content['message'];
                }

                if (is_string($content['error'] ?? null)) {
                    return $content['error'];
                }
            }
        } catch (JsonException) {
            return $body !== '' ? $body : null;
        }

        return null;
    }

    private function isAuthenticationError(string $message): bool
    {
        $message = mb_strtolower($message);

        return str_contains($message, 'invalid api key')
            || str_contains($message, 'invalid secret')
            || str_contains($message, 'authentication');
    }

    private function looksLikeJson(string $content): bool
    {
        $content = mb_trim($content);

        return (str_starts_with($content, '{') && str_ends_with($content, '}'))
            || (str_starts_with($content, '[') && str_ends_with($content, ']'));
    }

    private function getUserAgent(): string
    {
        try {
            $version = InstalledVersions::getPrettyVersion('porkbun-php/client') ?? 'dev';
        } catch (OutOfBoundsException) {
            $version = 'dev';
        }

        return "porkbun-php-client/{$version}";
    }
}
