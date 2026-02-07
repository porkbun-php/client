<?php

declare(strict_types=1);

use Porkbun\Api\Dns;
use Porkbun\Builder\DnsBatchBuilder;
use Porkbun\DTO\BatchOperationResult;

test('dns batch builder can add operations', function (): void {
    $batch = new DnsBatchBuilder();

    $batch
        ->addRecord('www', 'A', '192.0.2.1')
        ->addRecord('mail', 'MX', 'mail.example.com', 600, 10)
        ->editRecord(123, ['content' => '192.0.2.2'])
        ->deleteRecord(456)
        ->deleteByNameType('CNAME', 'old');

    expect($batch->getOperationsCount())->toBe(5);
});

test('dns batch builder can execute operations', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 123456],
        ['status' => 'SUCCESS'],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns($httpClient, 'example.com');

    $batch = new DnsBatchBuilder();
    $results = $batch
        ->addRecord('www', 'A', '192.0.2.1')
        ->editRecord(123, ['content' => '192.0.2.2'])
        ->execute($dns);

    expect($results)->toHaveCount(2);

    expect($results[0])->toBeInstanceOf(BatchOperationResult::class);
    expect($results[0]->isSuccess())->toBeTrue();
    expect($results[0]->operation)->toBe('create');
    expect($results[0]->recordId)->toBe(123456);

    expect($results[1])->toBeInstanceOf(BatchOperationResult::class);
    expect($results[1]->isSuccess())->toBeTrue();
    expect($results[1]->operation)->toBe('edit');
});

test('dns batch builder handles errors gracefully', function (): void {
    $mockClient = createMockHttpClient([
        ['body' => ['status' => 'ERROR', 'message' => 'Invalid API key'], 'httpStatus' => 403],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns($httpClient, 'example.com');

    $batch = new DnsBatchBuilder();
    $results = $batch
        ->addRecord('www', 'A', '192.0.2.1')
        ->execute($dns);

    expect($results)->toHaveCount(1);

    expect($results[0])->toBeInstanceOf(BatchOperationResult::class);
    expect($results[0]->isFailure())->toBeTrue();
    expect($results[0]->operation)->toBe('create');
    expect($results[0]->error)->toContain('Authentication');
});

test('dns batch builder clears operations after execute', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 123],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns($httpClient, 'example.com');

    $batch = new DnsBatchBuilder();
    $batch->addRecord('www', 'A', '192.0.2.1');

    expect($batch->getOperationsCount())->toBe(1);

    $batch->execute($dns);
    expect($batch->getOperationsCount())->toBe(0);
});

test('dns batch builder can rollback operations', function (): void {
    $batch = new DnsBatchBuilder();
    $batch->addRecord('www', 'A', '192.0.2.1');

    expect($batch->getOperationsCount())->toBe(1);

    $batch->rollback();
    expect($batch->getOperationsCount())->toBe(0);
});

test('dns batch builder is fluent', function (): void {
    $batch = new DnsBatchBuilder();

    $dnsBatchBuilder = $batch
        ->addRecord('www', 'A', '192.0.2.1')
        ->editRecord(123, ['ttl' => '7200'])
        ->deleteRecord(456);

    expect($dnsBatchBuilder)->toBe($batch);
});
