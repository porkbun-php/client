<?php

declare(strict_types=1);

namespace Porkbun\Exception;

use Porkbun\DTO\NextAction;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class RateLimitException extends ApiException
{
    public function __construct(
        string $message = 'Rate limit exceeded',
        int $statusCode = 429,
        ?Throwable $previous = null,
        ?RequestInterface $request = null,
        ?ResponseInterface $response = null,
        ?string $errorCode = null,
        ?NextAction $nextAction = null,
        ?string $requestId = null,
        private readonly ?int $retryAfter = null,
    ) {
        parent::__construct($message, $statusCode, $previous, $request, $response, $errorCode, $nextAction, $requestId);
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
