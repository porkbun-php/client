<?php

declare(strict_types=1);

use Porkbun\Api\Dns;
use Porkbun\Builder\DnsBatchBuilder;
use Porkbun\Builder\DnsRecordBuilder;
use Porkbun\DTO\BatchOperationResult;
use Porkbun\Exception\InvalidArgumentException;

test('dns batch builder can add operations', function (): void {
    $batch = new DnsBatchBuilder();

    $batch
        ->addRecord('A', 'www', '192.0.2.1')
        ->addRecord('MX', 'mail', 'mail.example.com', 600, 10)
        ->updateRecord(123, 'A', 'www', '192.0.2.2')
        ->deleteRecord(456)
        ->deleteByNameType('CNAME', 'old');

    expect($batch->operationsCount())->toBe(5);
});

test('dns batch builder can execute operations', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 123456],
        ['status' => 'SUCCESS'],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $batch = new DnsBatchBuilder();
    $results = $batch
        ->addRecord('A', 'www', '192.0.2.1')
        ->updateRecord(123, 'A', 'www', '192.0.2.2')
        ->execute($dns);

    expect($results)->toHaveCount(2);

    expect($results[0])->toBeInstanceOf(BatchOperationResult::class);
    expect($results[0]->success)->toBeTrue();
    expect($results[0]->operation)->toBe('create');
    expect($results[0]->recordId)->toBe(123456);

    expect($results[1])->toBeInstanceOf(BatchOperationResult::class);
    expect($results[1]->success)->toBeTrue();
    expect($results[1]->operation)->toBe('update');
});

test('dns batch builder handles errors gracefully', function (): void {
    $mockClient = createMockHttpClient([
        ['body' => ['status' => 'ERROR', 'message' => 'Invalid API key'], 'httpStatus' => 403],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $batch = new DnsBatchBuilder();
    $results = $batch
        ->addRecord('A', 'www', '192.0.2.1')
        ->execute($dns);

    expect($results)->toHaveCount(1);

    expect($results[0])->toBeInstanceOf(BatchOperationResult::class);
    expect($results[0]->isFailure)->toBeTrue();
    expect($results[0]->operation)->toBe('create');
    expect($results[0]->error)->toContain('Authentication');
});

test('dns batch builder clears operations after execute', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 123],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $batch = new DnsBatchBuilder();
    $batch->addRecord('A', 'www', '192.0.2.1');

    expect($batch->operationsCount())->toBe(1);

    $batch->execute($dns);
    expect($batch->operationsCount())->toBe(0);
});

test('dns batch builder can rollback operations', function (): void {
    $batch = new DnsBatchBuilder();
    $batch->addRecord('A', 'www', '192.0.2.1');

    expect($batch->operationsCount())->toBe(1);

    $batch->rollback();
    expect($batch->operationsCount())->toBe(0);
});

test('dns batch builder is fluent', function (): void {
    $batch = new DnsBatchBuilder();

    $dnsBatchBuilder = $batch
        ->add(new DnsRecordBuilder()->name('www')->a('192.0.2.1'))
        ->addRecord('A', 'api', '192.0.2.2')
        ->updateRecord(123, 'A', 'www', '192.0.2.1', ttl: 7200)
        ->deleteRecord(456);

    expect($dnsBatchBuilder)->toBe($batch);
});

test('dns batch builder accepts builder via add method', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 111],
        ['status' => 'SUCCESS', 'id' => 222],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $batch = new DnsBatchBuilder();
    $results = $batch
        ->add(new DnsRecordBuilder()->name('www')->a('192.0.2.1')->ttl(3600))
        ->add(new DnsRecordBuilder()->name('mail')->mx('mail.example.com', 10))
        ->execute($dns);

    expect($results)->toHaveCount(2);
    expect($results[0]->success)->toBeTrue();
    expect($results[0]->recordId)->toBe(111);
    expect($results[1]->success)->toBeTrue();
    expect($results[1]->recordId)->toBe(222);
});

test('dns batch builder add method mixed with raw methods', function (): void {
    $batch = new DnsBatchBuilder();

    $batch
        ->add(new DnsRecordBuilder()->name('www')->a('192.0.2.1'))
        ->addRecord('A', 'api', '192.0.2.2')
        ->updateRecord(123, 'A', 'api', '10.0.0.1')
        ->add(new DnsRecordBuilder()->name('_dmarc')->txt('v=DMARC1; p=reject'))
        ->deleteRecord(456);

    expect($batch->operationsCount())->toBe(5);
});

test('dns batch builder add method validates builder', function (): void {
    $batch = new DnsBatchBuilder();

    // Incomplete builder (no type/content) should throw via data()
    expect(fn (): DnsBatchBuilder => $batch->add(new DnsRecordBuilder()))
        ->toThrow(InvalidArgumentException::class, 'Record type is required');
});

test('dns batch builder add method works with shared template', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 1],
        ['status' => 'SUCCESS', 'id' => 2],
        ['status' => 'SUCCESS', 'id' => 3],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $dnsRecordBuilder = new DnsRecordBuilder()->ttl(3600);
    $batch = new DnsBatchBuilder();
    $results = $batch
        ->add($dnsRecordBuilder->name('web1')->a('10.0.1.1'))
        ->add($dnsRecordBuilder->name('web2')->a('10.0.1.2'))
        ->add($dnsRecordBuilder->name('web3')->a('10.0.1.3'))
        ->execute($dns);

    expect($results)->toHaveCount(3);
    expect($results[0]->success)->toBeTrue();
    expect($results[1]->success)->toBeTrue();
    expect($results[2]->success)->toBeTrue();
});
