<?php

declare(strict_types=1);

namespace Porkbun;

use Composer\InstalledVersions;
use JsonException;
use OutOfBoundsException;
use Porkbun\DTO\NextAction;
use Porkbun\Enum\Endpoint;
use Porkbun\Exception\ApiException;
use Porkbun\Exception\AuthenticationException;
use Porkbun\Exception\InvalidArgumentException;
use Porkbun\Exception\NetworkException;
use Porkbun\Exception\PorkbunApiException;
use Porkbun\Exception\RateLimitException;
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

    private static ?string $userAgent = null;

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
     * @throws AuthenticationException
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

        if ($statusCode >= 400) {
            throw $this->createException($this->decodeErrorBody($body), $request, $response);
        }

        $content = $this->decodeJson($body, $statusCode, $request, $response);

        if (!isset($content['status'])) {
            throw new ApiException('Invalid API response format: missing status field', $statusCode, null, $request, $response);
        }

        if ($content['status'] !== 'SUCCESS') {
            throw $this->createException($content, $request, $response);
        }

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

    private function decodeErrorBody(string $body): array
    {
        if (!$this->looksLikeJson($body)) {
            return [];
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function createException(array $error, RequestInterface $request, ResponseInterface $response): PorkbunApiException
    {
        $statusCode = $response->getStatusCode();
        $code = is_string($error['code'] ?? null) ? $error['code'] : null;
        $message = $this->errorMessage($error, $statusCode);
        $nextAction = is_array($error['next_action'] ?? null) ? NextAction::fromArray($error['next_action']) : null;
        $requestId = $error['requestId'] ?? $response->getHeaderLine('X-Request-Id');
        $requestId = is_string($requestId) && $requestId !== '' ? $requestId : null;

        if ($statusCode === 429 || $code === 'RATE_LIMIT_EXCEEDED') {
            return new RateLimitException($message, $statusCode, null, $request, $response, $code, $nextAction, $requestId, $this->retryAfter($error, $response));
        }

        if ($statusCode === 403 || $this->isAuthenticationError($code, $message)) {
            return new AuthenticationException($message, $statusCode, null, $request, $response, $code, $nextAction, $requestId);
        }

        return new ApiException($message, $statusCode, null, $request, $response, $code, $nextAction, $requestId);
    }

    private function errorMessage(array $error, int $statusCode): string
    {
        foreach (['message', 'error'] as $key) {
            if (is_string($error[$key] ?? null) && $error[$key] !== '') {
                return $error[$key];
            }
        }

        return match (true) {
            $statusCode === 403 => 'Authentication required or invalid',
            $statusCode >= 400 => "HTTP {$statusCode} error",
            default => 'Unknown API error',
        };
    }

    private function retryAfter(array $error, ResponseInterface $response): ?int
    {
        $header = $response->getHeaderLine('Retry-After');

        if (is_numeric($header)) {
            return (int) $header;
        }

        return is_numeric($error['ttlRemaining'] ?? null) ? (int) $error['ttlRemaining'] : null;
    }

    private function isAuthenticationError(?string $code, string $message): bool
    {
        if ($code !== null) {
            return in_array($code, ['API_KEY_REQUIRED', 'INVALID_TOKEN', 'INVALID_USER'], true)
                || str_starts_with($code, 'INVALID_API_KEYS');
        }

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
        if (self::$userAgent !== null) {
            return self::$userAgent;
        }

        try {
            $version = InstalledVersions::getPrettyVersion('porkbun-php/client') ?? 'dev';
        } catch (OutOfBoundsException) {
            $version = 'dev';
        }

        return self::$userAgent = "porkbun-php-client/{$version}";
    }
}
