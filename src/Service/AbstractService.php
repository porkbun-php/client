<?php

declare(strict_types=1);

namespace Porkbun\Service;

use Http\Discovery\Psr17FactoryDiscovery;
use JsonException;
use Porkbun\Config;
use Porkbun\Exception\ApiException;
use Porkbun\Exception\AuthenticationException;
use Porkbun\Exception\NetworkException;
use Porkbun\Middleware\MiddlewareInterface;
use Porkbun\Request\AbstractRequest;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;

abstract class AbstractService
{
    private RequestFactoryInterface $requestFactory;

    private StreamFactoryInterface $streamFactory;

    /** @var MiddlewareInterface[] */
    private array $middlewares = [];

    public function __construct(
        protected HttpClientInterface $httpClient,
        protected Config $config
    ) {
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
    }

    protected function request(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->config->getBaseUrl() . '/' . ltrim($endpoint, '/');

        if ($this->requiresAuth() && $this->config->hasAuth()) {
            $data = array_merge($data, $this->config->getAuthPayload());
        }

        $request = $this->createRequest($method, $url, $data);

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new NetworkException('HTTP request failed: ' . $e->getMessage(), 0, $e);
        }

        return $this->handleResponse($response);
    }

    protected function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, $data);
    }

    protected function get(string $endpoint, array $data = []): array
    {
        return $this->request('GET', $endpoint, $data);
    }

    private function createRequest(string $method, string $url, array $data): RequestInterface
    {
        $request = $this->requestFactory->createRequest($method, $url);

        if ($data !== []) {
            $body = $this->streamFactory->createStream(json_encode($data, JSON_THROW_ON_ERROR));
            $request = $request->withBody($body)
                ->withHeader('Content-Type', 'application/json');
        }

        return $request->withHeader('User-Agent', 'porkbun-php-api/1.0');
    }

    private function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $content = $response->getBody()->getContents();

        // Try to parse JSON response even for error status codes
        $data = null;

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            // If JSON parsing fails, we'll handle it below
        }

        if ($statusCode === 403) {
            $message = 'Authentication required or invalid';
            if (is_array($data) && isset($data['message'])) {
                $message .= ': ' . $data['message'];
            }

            throw new AuthenticationException($message);
        }

        if ($statusCode >= 400) {
            $message = 'HTTP ' . $statusCode;
            if (is_array($data) && isset($data['message'])) {
                $message .= ': ' . $data['message'];
            } elseif ($content !== '' && $content !== '0') {
                $message .= ': ' . trim($content);
            }

            throw new ApiException($message, $statusCode);
        }

        if (!is_array($data) || !isset($data['status'])) {
            throw new ApiException('Invalid API response format');
        }

        if ($data['status'] !== 'SUCCESS') {
            $message = is_string($data['message'] ?? null) ? $data['message'] : 'Unknown API error';

            throw new ApiException($message);
        }

        return $data;
    }

    // Middleware Methods

    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    public function removeMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middlewares = array_filter($this->middlewares, fn ($m): bool => $m !== $middleware);

        return $this;
    }

    public function clearMiddlewares(): self
    {
        $this->middlewares = [];

        return $this;
    }

    // Request/Response Pattern Support

    protected function sendRequest(AbstractRequest $apiRequest): array
    {
        try {
            $url = $this->config->getBaseUrl() . '/' . ltrim($apiRequest->getEndpoint(), '/');

            $data = $apiRequest->getData();
            if ($apiRequest->requiresAuth() && $this->config->hasAuth()) {
                $data = array_merge($data, $this->config->getAuthPayload());
            }

            $httpRequest = $this->createRequest($apiRequest->getMethod(), $url, $data);

            // Apply before middleware
            foreach ($this->middlewares as $middleware) {
                $httpRequest = $middleware->beforeRequest($apiRequest, $httpRequest);
            }

            $httpResponse = $this->httpClient->sendRequest($httpRequest);
            $responseData = $this->handleResponse($httpResponse);

            // Apply after middleware
            foreach ($this->middlewares as $middleware) {
                $responseData = $middleware->afterResponse($apiRequest, $httpResponse, $responseData);
            }

            return $responseData;

        } catch (Throwable $e) {
            // Apply error middleware
            foreach ($this->middlewares as $middleware) {
                $e = $middleware->onError($apiRequest, $e) ?? $e;
            }

            throw $e;
        }
    }

    abstract protected function requiresAuth(): bool;
}
