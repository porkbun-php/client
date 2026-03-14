<?php

declare(strict_types=1);

use Porkbun\DTO\BatchOperationResult;
use Porkbun\Enum\BatchOperationType;
use Porkbun\Enum\DnsRecordType;

test('success creates successful result', function (): void {
    $batchOperationResult = BatchOperationResult::success(BatchOperationType::CREATE, 123, DnsRecordType::A);

    expect($batchOperationResult->operation)->toBe(BatchOperationType::CREATE)
        ->and($batchOperationResult->success)->toBeTrue()
        ->and($batchOperationResult->recordId)->toBe(123)
        ->and($batchOperationResult->recordType)->toBe(DnsRecordType::A)
        ->and($batchOperationResult->error)->toBeNull();
});

test('success without record details', function (): void {
    $batchOperationResult = BatchOperationResult::success(BatchOperationType::DELETE);

    expect($batchOperationResult->operation)->toBe(BatchOperationType::DELETE)
        ->and($batchOperationResult->success)->toBeTrue()
        ->and($batchOperationResult->recordId)->toBeNull()
        ->and($batchOperationResult->recordType)->toBeNull();
});

test('failure creates failed result', function (): void {
    $batchOperationResult = BatchOperationResult::failure(BatchOperationType::UPDATE, 'Record not found');

    expect($batchOperationResult->operation)->toBe(BatchOperationType::UPDATE)
        ->and($batchOperationResult->success)->toBeFalse()
        ->and($batchOperationResult->error)->toBe('Record not found')
        ->and($batchOperationResult->recordId)->toBeNull();
});

test('isSuccess returns correct value', function (): void {
    $success = BatchOperationResult::success(BatchOperationType::CREATE, 123);
    $failure = BatchOperationResult::failure(BatchOperationType::CREATE, 'Error');

    expect($success->success)->toBeTrue()
        ->and($failure->success)->toBeFalse();
});

test('isFailure returns correct value', function (): void {
    $success = BatchOperationResult::success(BatchOperationType::CREATE, 123);
    $failure = BatchOperationResult::failure(BatchOperationType::CREATE, 'Error');

    expect($success->isFailure)->toBeFalse()
        ->and($failure->isFailure)->toBeTrue();
});

test('hasRecordId checks for record id presence', function (): void {
    $batchOperationResult = BatchOperationResult::success(BatchOperationType::CREATE, 123);
    $withoutId = BatchOperationResult::success(BatchOperationType::DELETE);

    expect($batchOperationResult->hasRecordId)->toBeTrue()
        ->and($withoutId->hasRecordId)->toBeFalse();
});

test('toArray serializes success result', function (): void {
    $batchOperationResult = BatchOperationResult::success(BatchOperationType::CREATE, 123, DnsRecordType::A);

    expect($batchOperationResult->toArray())->toBe([
        'operation' => 'create',
        'status' => 'SUCCESS',
        'id' => 123,
        'type' => 'A',
    ]);
});

test('toArray serializes failure result', function (): void {
    $batchOperationResult = BatchOperationResult::failure(BatchOperationType::UPDATE, 'Record not found');

    expect($batchOperationResult->toArray())->toBe([
        'operation' => 'update',
        'status' => 'ERROR',
        'error' => 'Record not found',
    ]);
});

test('toArray omits null fields', function (): void {
    $batchOperationResult = BatchOperationResult::success(BatchOperationType::DELETE);

    expect($batchOperationResult->toArray())->toBe([
        'operation' => 'delete',
        'status' => 'SUCCESS',
    ]);
});
