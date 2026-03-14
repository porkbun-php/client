<?php

declare(strict_types=1);

namespace Porkbun\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class NetworkException extends PorkbunApiException
{
    public function __construct(
        string $message = 'Network or HTTP client error',
        int $statusCode = 0,
        ?Throwable $previous = null,
        ?RequestInterface $request = null,
        ?ResponseInterface $response = null,
    ) {
        parent::__construct($message, $statusCode, $previous, $request, $response);
    }
}
