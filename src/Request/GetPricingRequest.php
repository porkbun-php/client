<?php

declare(strict_types=1);

namespace Porkbun\Request;

class GetPricingRequest extends AbstractRequest
{
    public function getEndpoint(): string
    {
        return '/pricing/get';
    }

    public function getMethod(): string
    {
        return 'POST';
    }

    public function getData(): array
    {
        return [];
    }

    public function requiresAuth(): bool
    {
        return false;
    }
}
