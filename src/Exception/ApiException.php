<?php

declare(strict_types=1);

namespace Porkbun\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class ApiException extends PorkbunApiException
{
    public function __construct(
        string $message = '',
        private readonly int $statusCode = 0,
        ?Throwable $throwable = null,
        private readonly ?RequestInterface $request = null,
        private readonly ?ResponseInterface $response = null
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

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function hasRequest(): bool
    {
        return $this->request instanceof RequestInterface;
    }

    public function hasResponse(): bool
    {
        return $this->response instanceof ResponseInterface;
    }
}
