<?php

declare(strict_types=1);

namespace Porkbun\Exception;

use Psr\Http\Message\RequestInterface;
use Throwable;

final class NetworkException extends PorkbunApiException
{
    public function __construct(
        string $message = 'Network or HTTP client error',
        int $statusCode = 0,
        ?Throwable $throwable = null,
        ?RequestInterface $request = null
    ) {
        parent::__construct($message, $statusCode, $throwable, $request);
    }
}
