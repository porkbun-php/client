<?php

declare(strict_types=1);

namespace Porkbun\Enum;

use Porkbun\Exception\InvalidArgumentException;

enum UrlForwardType: string
{
    case PERMANENT = 'permanent';
    case TEMPORARY = 'temporary';
    case MASKED = 'masked';

    public static function resolve(string|self $type): self
    {
        if ($type instanceof self) {
            return $type;
        }

        return self::tryFrom(mb_strtolower($type))
            ?? throw new InvalidArgumentException("Invalid URL forward type: {$type}. Valid types: permanent, temporary, masked");
    }
}
