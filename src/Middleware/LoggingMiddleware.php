<?php

declare(strict_types=1);

namespace Porkbun\Middleware;

use Porkbun\Request\AbstractRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class LoggingMiddleware extends AbstractMiddleware
{
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function beforeRequest(AbstractRequest $apiRequest, RequestInterface $httpRequest): RequestInterface
    {
        $this->logger->info('API Request', [
            'method' => $apiRequest->getMethod(),
            'endpoint' => $apiRequest->getEndpoint(),
            'url' => (string) $httpRequest->getUri(),
        ]);

        return $httpRequest;
    }

    public function afterResponse(AbstractRequest $apiRequest, ResponseInterface $httpResponse, array $responseData): array
    {
        $this->logger->info('API Response', [
            'endpoint' => $apiRequest->getEndpoint(),
            'status_code' => $httpResponse->getStatusCode(),
            'success' => ($responseData['status'] ?? '') === 'SUCCESS',
        ]);

        return $responseData;
    }

    public function onError(AbstractRequest $apiRequest, Throwable $throwable): ?Throwable
    {
        $this->logger->error('API Error', [
            'endpoint' => $apiRequest->getEndpoint(),
            'error' => $throwable->getMessage(),
            'exception' => $throwable::class,
        ]);

        return $throwable;
    }
}
