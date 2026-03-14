<?php

declare(strict_types=1);

namespace Porkbun\Enum;

enum Endpoint: string
{
    case DEFAULT = 'https://api.porkbun.com/api/json/v3';
    case IPV4 = 'https://api-ipv4.porkbun.com/api/json/v3';

    public function url(): string
    {
        return $this->value;
    }
}
