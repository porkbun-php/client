<?php

declare(strict_types=1);

namespace Porkbun\Request;

abstract class AbstractRequest
{
    abstract public function getEndpoint(): string;

    abstract public function getMethod(): string;

    abstract public function getData(): array;

    public function requiresAuth(): bool
    {
        return true;
    }
}
