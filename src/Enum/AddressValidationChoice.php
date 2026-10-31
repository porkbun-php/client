<?php

declare(strict_types=1);

namespace Porkbun\Enum;

use Porkbun\Exception\InvalidArgumentException;

enum AddressValidationChoice: string
{
    case ACCEPT_SUGGESTION = 'accept_suggestion';
    case USE_AS_ENTERED = 'use_as_entered';

    public static function resolve(string|self $choice): self
    {
        if ($choice instanceof self) {
            return $choice;
        }

        return self::tryFrom(mb_strtolower($choice))
            ?? throw new InvalidArgumentException("Invalid address validation choice: {$choice}. Valid choices: accept_suggestion, use_as_entered");
    }
}
