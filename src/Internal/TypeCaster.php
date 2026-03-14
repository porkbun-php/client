<?php

declare(strict_types=1);

namespace Porkbun\Internal;

final class TypeCaster
{
    public static function toBool(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'yes'], true);
    }

    public static function toPrice(string|int|float $value): float
    {
        return (float) str_replace(',', '', (string) $value);
    }
}
