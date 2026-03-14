<?php

declare(strict_types=1);

use Porkbun\Api\Dns;
use Porkbun\Builder\DnsBatchBuilder;
use Porkbun\Builder\DnsRecordBuilder;
use Porkbun\DTO\BatchOperationResult;
use Porkbun\DTO\BatchResult;
use Porkbun\Enum\BatchOperationType;
use Porkbun\Enum\DnsRecordType;
use Porkbun\Exception\InvalidArgumentException;

test('dns batch builder can add operations', function (): void {
    $batch = new DnsBatchBuilder()
        ->addRecord('A', 'www', '192.0.2.1')
        ->addRecord('MX', 'mail', 'mail.example.com', 600, 10)
        ->updateRecord(123, 'A', 'www', '192.0.2.2')
        ->deleteRecord(456)
        ->deleteByType('CNAME', 'old');

    expect($batch->count())->toBe(5);
});

test('dns batch builder can execute operations', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 123456],
        ['status' => 'SUCCESS'],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $batch = new DnsBatchBuilder()
        ->addRecord('A', 'www', '192.0.2.1')
        ->updateRecord(123, 'A', 'www', '192.0.2.2');

    $results = $batch->execute($dns);

    expect($results)->toHaveCount(2);

    expect($results->items()[0])->toBeInstanceOf(BatchOperationResult::class);
    expect($results->items()[0]->success)->toBeTrue();
    expect($results->items()[0]->operation)->toBe(BatchOperationType::CREATE);
    expect($results->items()[0]->recordId)->toBe(123456);

    expect($results->items()[1])->toBeInstanceOf(BatchOperationResult::class);
    expect($results->items()[1]->success)->toBeTrue();
    expect($results->items()[1]->operation)->toBe(BatchOperationType::UPDATE);
});

test('dns batch builder handles errors gracefully', function (): void {
    $mockClient = createMockHttpClient([
        ['body' => ['status' => 'ERROR', 'message' => 'Invalid API key'], 'httpStatus' => 403],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $batch = new DnsBatchBuilder()
        ->addRecord('A', 'www', '192.0.2.1');

    $results = $batch->execute($dns);

    expect($results)->toHaveCount(1);

    expect($results->items()[0])->toBeInstanceOf(BatchOperationResult::class);
    expect($results->items()[0]->isFailure)->toBeTrue();
    expect($results->items()[0]->operation)->toBe(BatchOperationType::CREATE);
    expect($results->items()[0]->error)->toContain('Authentication');
});

test('dns batch builder preserves operations after execute', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 123],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $batch = new DnsBatchBuilder()
        ->addRecord('A', 'www', '192.0.2.1');

    expect($batch->count())->toBe(1);

    $batch->execute($dns);
    expect($batch->count())->toBe(1);
});

test('dns batch builder can clear operations', function (): void {
    $batch = new DnsBatchBuilder()
        ->addRecord('A', 'www', '192.0.2.1');

    expect($batch->count())->toBe(1);

    $cleared = $batch->clear();
    expect($cleared->count())->toBe(0);
    expect($batch->count())->toBe(1);
});

test('dns batch builder is immutable', function (): void {
    $batch = new DnsBatchBuilder();

    $withOne = $batch->addRecord('A', 'www', '192.0.2.1');
    $withTwo = $withOne->addRecord('A', 'api', '192.0.2.2');

    expect($batch->count())->toBe(0);
    expect($withOne->count())->toBe(1);
    expect($withTwo->count())->toBe(2);
});

test('dns batch builder is fluent', function (): void {
    $batch = new DnsBatchBuilder()
        ->add(new DnsRecordBuilder()->name('www')->a('192.0.2.1'))
        ->addRecord('A', 'api', '192.0.2.2')
        ->updateRecord(123, 'A', 'www', '192.0.2.1', ttl: 7200)
        ->deleteRecord(456);

    expect($batch->count())->toBe(4);
});

test('dns batch builder accepts builder via add method', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 111],
        ['status' => 'SUCCESS', 'id' => 222],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $batch = new DnsBatchBuilder()
        ->add(new DnsRecordBuilder()->name('www')->a('192.0.2.1')->ttl(3600))
        ->add(new DnsRecordBuilder()->name('mail')->mx('mail.example.com', 10));

    $results = $batch->execute($dns);

    expect($results)->toHaveCount(2);
    expect($results->items()[0]->success)->toBeTrue();
    expect($results->items()[0]->recordId)->toBe(111);
    expect($results->items()[1]->success)->toBeTrue();
    expect($results->items()[1]->recordId)->toBe(222);
});

test('dns batch builder add method mixed with raw methods', function (): void {
    $batch = new DnsBatchBuilder()
        ->add(new DnsRecordBuilder()->name('www')->a('192.0.2.1'))
        ->addRecord('A', 'api', '192.0.2.2')
        ->updateRecord(123, 'A', 'api', '10.0.0.1')
        ->add(new DnsRecordBuilder()->name('_dmarc')->txt('v=DMARC1; p=reject'))
        ->deleteRecord(456);

    expect($batch->count())->toBe(5);
});

test('dns batch builder add method validates builder', function (): void {
    $batch = new DnsBatchBuilder();

    // Incomplete builder (no type/content) should throw via data()
    expect(fn (): DnsBatchBuilder => $batch->add(new DnsRecordBuilder()))
        ->toThrow(InvalidArgumentException::class, 'Record type is required');
});

test('dns batch builder executes empty batch gracefully', function (): void {
    $mockClient = createMockHttpClient([]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $batch = new DnsBatchBuilder();
    $results = $batch->execute($dns);

    expect($results)->toBeInstanceOf(BatchResult::class)
        ->and($results->isEmpty())->toBeTrue();
});

test('dns batch builder can execute delete by type', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS'],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $batch = new DnsBatchBuilder()
        ->deleteByType('CNAME', 'old');

    $results = $batch->execute($dns);

    expect($results)->toHaveCount(1);
    expect($results->items()[0])->toBeInstanceOf(BatchOperationResult::class);
    expect($results->items()[0]->success)->toBeTrue();
    expect($results->items()[0]->operation)->toBe(BatchOperationType::DELETE_BY_NAME_TYPE);
    expect($results->items()[0]->recordType)->toBe(DnsRecordType::CNAME);
});

test('dns batch builder can execute delete by type without name', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS'],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $batch = new DnsBatchBuilder()
        ->deleteByType('A');

    $results = $batch->execute($dns);

    expect($results)->toHaveCount(1);
    expect($results->items()[0]->success)->toBeTrue();
    expect($results->items()[0]->operation)->toBe(BatchOperationType::DELETE_BY_NAME_TYPE);
});

test('dns batch builder can execute delete by id', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS'],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $batch = new DnsBatchBuilder()
        ->deleteRecord(789);

    $results = $batch->execute($dns);

    expect($results)->toHaveCount(1);
    expect($results->items()[0]->success)->toBeTrue();
    expect($results->items()[0]->operation)->toBe(BatchOperationType::DELETE);
    expect($results->items()[0]->recordId)->toBe(789);
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
    $batch = new DnsBatchBuilder()
        ->add($dnsRecordBuilder->name('web1')->a('10.0.1.1'))
        ->add($dnsRecordBuilder->name('web2')->a('10.0.1.2'))
        ->add($dnsRecordBuilder->name('web3')->a('10.0.1.3'));

    $results = $batch->execute($dns);

    expect($results)->toHaveCount(3);
    expect($results->items()[0]->success)->toBeTrue();
    expect($results->items()[1]->success)->toBeTrue();
    expect($results->items()[2]->success)->toBeTrue();
});

test('dns batch builder can be created via dns batch method', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 100],
        ['status' => 'SUCCESS'],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $results = $dns->batch()
        ->addRecord('A', 'www', '192.0.2.1')
        ->deleteRecord(456)
        ->execute();

    expect($results)->toHaveCount(2);
    expect($results->items()[0]->success)->toBeTrue();
    expect($results->items()[0]->recordId)->toBe(100);
    expect($results->items()[1]->success)->toBeTrue();
    expect($results->items()[1]->operation)->toBe(BatchOperationType::DELETE);
});

test('dns batch builder pre-wired via batch preserves dns after clear', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 1],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $cleared = $dns->batch()
        ->addRecord('A', 'www', '192.0.2.1')
        ->clear();

    expect($cleared->count())->toBe(0);

    $results = $cleared->addRecord('A', 'api', '192.0.2.2')->execute();

    expect($results)->toHaveCount(1);
    expect($results->items()[0]->success)->toBeTrue();
});

test('dns batch builder throws when execute called without dns', function (): void {
    $batch = new DnsBatchBuilder()
        ->addRecord('A', 'www', '192.0.2.1');

    expect(fn (): BatchResult => $batch->execute())
        ->toThrow(InvalidArgumentException::class, 'No Dns instance provided');
});

test('dns batch builder execute argument overrides pre-wired dns', function (): void {
    $mockClient1 = createMockHttpClient([]);
    $httpClient1 = createHttpClient($mockClient1, 'pk1_key', 'sk1_secret');
    $dns1 = new Dns(createMockContext($httpClient1), 'wired.com');

    $mockClient2 = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 999],
    ]);
    $httpClient2 = createHttpClient($mockClient2, 'pk1_key', 'sk1_secret');
    $dns2 = new Dns(createMockContext($httpClient2), 'override.com');

    $batch = $dns1->batch()->addRecord('A', 'www', '192.0.2.1');

    $results = $batch->execute($dns2);

    expect($results)->toHaveCount(1);
    expect($results->items()[0]->success)->toBeTrue();
    expect($results->items()[0]->recordId)->toBe(999);
});
