<?php

declare(strict_types=1);

use Porkbun\DTO\ContactsUpdateResult;

test('it creates contacts update result from array', function (): void {
    $result = ContactsUpdateResult::fromArray([
        'status' => 'SUCCESS',
        'message' => 'Contacts updated.',
        'registrantChanged' => true,
    ]);

    expect($result->registrantChanged)->toBeTrue()
        ->and($result->message)->toBe('Contacts updated.');
});

test('it defaults registrantChanged to false', function (): void {
    $result = ContactsUpdateResult::fromArray(['status' => 'SUCCESS']);

    expect($result->registrantChanged)->toBeFalse()
        ->and($result->message)->toBeNull();
});

test('toArray omits null message', function (): void {
    $result = new ContactsUpdateResult(registrantChanged: false);

    expect($result->toArray())->toBe(['registrantChanged' => false])
        ->and($result->jsonSerialize())->toBe($result->toArray());
});
