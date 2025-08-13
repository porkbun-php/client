<?php

declare(strict_types=1);

namespace Porkbun\Exception;

use Throwable;

class ApiException extends PorkbunApiException
{
    public function __construct(string $message = '', private int $statusCode = 0, ?Throwable $throwable = null)
    {
        parent::__construct($message, 0, $throwable);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
