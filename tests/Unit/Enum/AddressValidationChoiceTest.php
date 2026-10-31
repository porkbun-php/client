<?php

declare(strict_types=1);

use Porkbun\Enum\AddressValidationChoice;
use Porkbun\Exception\InvalidArgumentException;

test('resolve returns enum instances unchanged', function (): void {
    expect(AddressValidationChoice::resolve(AddressValidationChoice::USE_AS_ENTERED))
        ->toBe(AddressValidationChoice::USE_AS_ENTERED);
});

test('resolve accepts strings in any case', function (): void {
    expect(AddressValidationChoice::resolve('accept_suggestion'))->toBe(AddressValidationChoice::ACCEPT_SUGGESTION)
        ->and(AddressValidationChoice::resolve('USE_AS_ENTERED'))->toBe(AddressValidationChoice::USE_AS_ENTERED);
});

test('resolve rejects unknown choices', function (): void {
    expect(fn (): AddressValidationChoice => AddressValidationChoice::resolve('maybe'))
        ->toThrow(InvalidArgumentException::class, 'Invalid address validation choice: maybe');
});
