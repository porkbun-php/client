<?php

declare(strict_types=1);

namespace Porkbun\Middleware;

use Porkbun\Request\AbstractRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

abstract class AbstractMiddleware implements MiddlewareInterface
{
    public function beforeRequest(AbstractRequest $apiRequest, RequestInterface $httpRequest): RequestInterface
    {
        return $httpRequest;
    }

    public function afterResponse(AbstractRequest $apiRequest, ResponseInterface $httpResponse, array $responseData): array
    {
        return $responseData;
    }

    public function onError(AbstractRequest $apiRequest, Throwable $throwable): ?Throwable
    {
        return $throwable;
    }
}
