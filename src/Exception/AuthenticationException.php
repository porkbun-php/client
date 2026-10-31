<?php

declare(strict_types=1);

namespace Porkbun\Exception;

use Porkbun\DTO\NextAction;
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
        ?ResponseInterface $response = null,
        ?string $errorCode = null,
        ?NextAction $nextAction = null,
        ?string $requestId = null,
    ) {
        parent::__construct($message, $statusCode, $previous, $request, $response, $errorCode, $nextAction, $requestId);
    }
}
