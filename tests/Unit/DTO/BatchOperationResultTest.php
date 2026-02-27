<?php

declare(strict_types=1);

use Porkbun\DTO\BatchOperationResult;

test('success creates successful result', function (): void {
    $batchOperationResult = BatchOperationResult::success('create', 123, 'A');

    expect($batchOperationResult->operation)->toBe('create')
        ->and($batchOperationResult->success)->toBeTrue()
        ->and($batchOperationResult->recordId)->toBe(123)
        ->and($batchOperationResult->recordType)->toBe('A')
        ->and($batchOperationResult->error)->toBeNull();
});

test('success without record details', function (): void {
    $batchOperationResult = BatchOperationResult::success('delete');

    expect($batchOperationResult->operation)->toBe('delete')
        ->and($batchOperationResult->success)->toBeTrue()
        ->and($batchOperationResult->recordId)->toBeNull()
        ->and($batchOperationResult->recordType)->toBeNull();
});

test('failure creates failed result', function (): void {
    $batchOperationResult = BatchOperationResult::failure('edit', 'Record not found');

    expect($batchOperationResult->operation)->toBe('edit')
        ->and($batchOperationResult->success)->toBeFalse()
        ->and($batchOperationResult->error)->toBe('Record not found')
        ->and($batchOperationResult->recordId)->toBeNull();
});

test('isSuccess returns correct value', function (): void {
    $success = BatchOperationResult::success('create', 123);
    $failure = BatchOperationResult::failure('create', 'Error');

    expect($success->success)->toBeTrue()
        ->and($failure->success)->toBeFalse();
});

test('isFailure returns correct value', function (): void {
    $success = BatchOperationResult::success('create', 123);
    $failure = BatchOperationResult::failure('create', 'Error');

    expect($success->isFailure)->toBeFalse()
        ->and($failure->isFailure)->toBeTrue();
});

test('hasRecordId checks for record id presence', function (): void {
    $batchOperationResult = BatchOperationResult::success('create', 123);
    $withoutId = BatchOperationResult::success('delete');

    expect($batchOperationResult->hasRecordId)->toBeTrue()
        ->and($withoutId->hasRecordId)->toBeFalse();
});

test('toArray serializes success result', function (): void {
    $batchOperationResult = BatchOperationResult::success('create', 123, 'A');

    expect($batchOperationResult->toArray())->toBe([
        'operation' => 'create',
        'status' => 'success',
        'id' => 123,
        'type' => 'A',
    ]);
});

test('toArray serializes failure result', function (): void {
    $batchOperationResult = BatchOperationResult::failure('edit', 'Record not found');

    expect($batchOperationResult->toArray())->toBe([
        'operation' => 'edit',
        'status' => 'error',
        'error' => 'Record not found',
    ]);
});

test('toArray omits null fields', function (): void {
    $batchOperationResult = BatchOperationResult::success('delete');

    expect($batchOperationResult->toArray())->toBe([
        'operation' => 'delete',
        'status' => 'success',
    ]);
});
