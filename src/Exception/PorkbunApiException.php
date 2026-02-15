<?php

declare(strict_types=1);

namespace Porkbun\Exception;

use Exception;
use Psr\Http\Message\RequestInterface;
use Throwable;

abstract class PorkbunApiException extends Exception implements ExceptionInterface
{
    public function __construct(
        string $message = '',
        private readonly int $statusCode = 0,
        ?Throwable $previous = null,
        private readonly ?RequestInterface $request = null,
    ) {
        parent::__construct($message, 0, $previous);
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
