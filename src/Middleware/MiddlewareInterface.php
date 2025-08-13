<?php

declare(strict_types=1);

namespace Porkbun\Middleware;

use Porkbun\Request\AbstractRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

interface MiddlewareInterface
{
    /**
     * Process the request before it's sent to the API
     */
    public function beforeRequest(AbstractRequest $apiRequest, RequestInterface $httpRequest): RequestInterface;

    /**
     * Process the response after it's received from the API
     */
    public function afterResponse(AbstractRequest $apiRequest, ResponseInterface $httpResponse, array $responseData): array;

    /**
     * Handle errors that occur during the request
     */
    public function onError(AbstractRequest $apiRequest, Throwable $throwable): ?Throwable;
}
