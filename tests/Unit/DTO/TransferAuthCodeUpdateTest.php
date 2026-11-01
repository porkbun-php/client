<?php

declare(strict_types=1);

use Porkbun\DTO\TransferAuthCodeUpdate;

test('transfer auth code update leaves missing fields null', function (): void {
    $update = TransferAuthCodeUpdate::fromArray(['domain' => 'example.com']);

    expect($update->authCodeValid)->toBeNull()
        ->and($update->previousStatus)->toBeNull()
        ->and($update->transferStatus)->toBeNull()
        ->and($update->message)->toBeNull()
        ->and($update->toArray())->toBe(['domain' => 'example.com']);
});

test('transfer auth code update keeps a rejected code', function (): void {
    $update = TransferAuthCodeUpdate::fromArray(['domain' => 'example.com', 'authCodeValid' => false]);

    expect($update->authCodeValid)->toBeFalse()
        ->and($update->toArray())->toBe(['domain' => 'example.com', 'authCodeValid' => false]);
});

test('transfer auth code update round trips through toArray', function (): void {
    $update = TransferAuthCodeUpdate::fromArray([
        'domain' => 'example.com',
        'authCodeValid' => true,
        'previousStatus' => 'PENDINGAUTH',
        'transferStatus' => 'PENDINGSUBMIT',
        'message' => 'Auth code replaced and transfer re-queued.',
    ]);

    expect(TransferAuthCodeUpdate::fromArray($update->toArray()))->toEqual($update)
        ->and($update->jsonSerialize())->toBe($update->toArray());
});
