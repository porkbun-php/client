<?php

declare(strict_types=1);

namespace Porkbun\Exception;

use Psr\Http\Message\RequestInterface;
use Throwable;

final class NetworkException extends PorkbunApiException
{
    public function __construct(
        string $message = 'Network or HTTP client error',
        private readonly int $statusCode = 0,
        ?Throwable $throwable = null,
        private readonly ?RequestInterface $request = null
    ) {
        parent::__construct($message, 0, $throwable);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getRequest(): ?RequestInterface
    {
        return $this->request;
    }

    public function hasRequest(): bool
    {
        return $this->request instanceof RequestInterface;
    }
}
