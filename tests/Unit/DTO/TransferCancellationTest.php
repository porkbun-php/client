<?php

declare(strict_types=1);

use Porkbun\DTO\TransferCancellation;

test('transfer cancellation leaves missing fields null', function (): void {
    $cancellation = TransferCancellation::fromArray(['status' => 'SUCCESS', 'domain' => 'example.com']);

    expect($cancellation->domain)->toBe('example.com')
        ->and($cancellation->cancelled)->toBeNull()
        ->and($cancellation->withdrawnAtRegistry)->toBeNull()
        ->and($cancellation->refunded)->toBeNull()
        ->and($cancellation->refundAmountInCents)->toBeNull()
        ->and($cancellation->orderId)->toBeNull()
        ->and($cancellation->toArray())->toBe(['domain' => 'example.com']);
});

test('transfer cancellation keeps a false flag', function (): void {
    $cancellation = TransferCancellation::fromArray(['domain' => 'example.com', 'cancelled' => true, 'refunded' => false]);

    expect($cancellation->refunded)->toBeFalse()
        ->and($cancellation->toArray())->toBe(['domain' => 'example.com', 'cancelled' => true, 'refunded' => false]);
});

test('transfer cancellation round trips through toArray', function (): void {
    $cancellation = TransferCancellation::fromArray([
        'domain' => 'example.com',
        'cancelled' => true,
        'previousStatus' => 'PENDINGTRANSFER',
        'transferStatus' => 'CANCELED',
        'withdrawnAtRegistry' => true,
        'registryResultCode' => '1000',
        'registryCode' => '1000',
        'refunded' => true,
        'refundAmount' => 1108,
        'orderId' => 9915398,
        'message' => 'Transfer cancelled and refunded.',
    ]);

    expect(TransferCancellation::fromArray($cancellation->toArray()))->toEqual($cancellation)
        ->and($cancellation->toArray()['refundAmountInCents'])->toBe(1108)
        ->and($cancellation->jsonSerialize())->toBe($cancellation->toArray());
});
