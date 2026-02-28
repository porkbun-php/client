<?php

declare(strict_types=1);

namespace Porkbun\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class AuthenticationException extends PorkbunApiException
{
    public function __construct(
        string $message = 'Authentication required or invalid',
        int $statusCode = 403,
        ?Throwable $previous = null,
        ?RequestInterface $request = null,
        private readonly ?ResponseInterface $response = null
    ) {
        parent::__construct($message, $statusCode, $previous, $request);
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function hasResponse(): bool
    {
        return $this->response instanceof ResponseInterface;
    }
}
