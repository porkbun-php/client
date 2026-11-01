<?php

declare(strict_types=1);

use Porkbun\DTO\DomainTransfer;

test('it creates a domain transfer from the api response', function (): void {
    $domainTransfer = DomainTransfer::fromArray([
        'domain' => 'example.com',
        'orderId' => 9915398,
        'transferId' => 788793,
        'message' => 'Transfer completed.',
        'limits' => [
            'attempts' => ['TTL' => 1, 'limit' => 1, 'used' => 1],
            'success' => ['TTL' => 86400, 'limit' => 1000, 'used' => 1],
        ],
        'balance' => 85596,
        'ttlRemaining' => 86399,
    ]);

    expect($domainTransfer->domain)->toBe('example.com')
        ->and($domainTransfer->orderId)->toBe(9915398)
        ->and($domainTransfer->transferId)->toBe(788793)
        ->and($domainTransfer->balanceInCents)->toBe(85596)
        ->and($domainTransfer->balanceInDollars)->toBe(855.96)
        ->and($domainTransfer->message)->toBe('Transfer completed.')
        ->and($domainTransfer->hasLimits)->toBeTrue()
        ->and($domainTransfer->remainingAttempts)->toBe(0)
        ->and($domainTransfer->remainingTransfers)->toBe(999);
});

test('domain transfer without limits', function (): void {
    $domainTransfer = DomainTransfer::fromArray(['domain' => 'example.com', 'orderId' => 1, 'transferId' => 2, 'balance' => 0]);

    expect($domainTransfer->hasLimits)->toBeFalse()
        ->and($domainTransfer->remainingAttempts)->toBeNull()
        ->and($domainTransfer->remainingTransfers)->toBeNull()
        ->and($domainTransfer->message)->toBeNull();
});

test('domain transfer round trips through toArray', function (): void {
    $domainTransfer = DomainTransfer::fromArray([
        'domain' => 'example.com',
        'orderId' => 9915398,
        'transferId' => 788793,
        'message' => 'Transfer completed.',
        'limits' => ['success' => ['limit' => 1000, 'used' => 1]],
        'balance' => 85596,
    ]);

    expect(DomainTransfer::fromArray($domainTransfer->toArray()))->toEqual($domainTransfer)
        ->and($domainTransfer->jsonSerialize())->toBe($domainTransfer->toArray());
});
