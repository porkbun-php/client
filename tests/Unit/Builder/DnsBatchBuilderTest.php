<?php

declare(strict_types=1);

use Porkbun\Builder\DnsBatchBuilder;
use Porkbun\Config;
use Porkbun\Service\DnsService;
use Psr\Http\Client\ClientInterface;

test('dns batch builder can add operations', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $dns = new DnsService($mock, $config, 'example.com');

    $batch = new DnsBatchBuilder($dns);

    $batch
        ->addRecord('www', 'A', '192.168.1.1')
        ->addRecord('mail', 'MX', 'mail.example.com', 600, 10)
        ->editRecord(123, ['content' => '192.168.1.2'])
        ->deleteRecord(456)
        ->deleteByNameType('CNAME', 'old');

    expect($batch->getOperationsCount())->toBe(5);
});

test('dns batch builder can commit operations', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    // Mock successful responses
    $createResponse = mockApiResponse(json_encode(['status' => 'SUCCESS', 'id' => '123456']));
    $createResponse->shouldReceive('getStatusCode')->andReturn(200);

    $editResponse = mockApiResponse(json_encode(['status' => 'SUCCESS']));
    $editResponse->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->times(2)->andReturn($createResponse, $editResponse);

    $dns = new DnsService($mock, $config, 'example.com');
    $batch = new DnsBatchBuilder($dns);

    $results = $batch
        ->addRecord('www', 'A', '192.168.1.1')
        ->editRecord(123, ['content' => '192.168.1.2'])
        ->commit();

    expect($results)->toHaveCount(2);
    expect($results[0]['status'])->toBe('success');
    expect($results[0]['operation'])->toBe('create');
    expect($results[0]['id'])->toBe(123456);

    expect($results[1]['status'])->toBe('success');
    expect($results[1]['operation'])->toBe('edit');
});

test('dns batch builder handles errors gracefully', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    // Mock error response
    $errorResponse = mockApiResponse('');
    $errorResponse->shouldReceive('getStatusCode')->andReturn(403);

    $mock->shouldReceive('sendRequest')->once()->andReturn($errorResponse);

    $dns = new DnsService($mock, $config, 'example.com');
    $batch = new DnsBatchBuilder($dns);

    $results = $batch
        ->addRecord('www', 'A', '192.168.1.1')
        ->commit();

    expect($results)->toHaveCount(1);
    expect($results[0]['status'])->toBe('error');
    expect($results[0]['operation'])->toBe('create');
    expect($results[0]['error'])->toContain('Authentication required');
});

test('dns batch builder clears operations after commit', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $dns = new DnsService($mock, $config, 'example.com');

    $batch = new DnsBatchBuilder($dns);
    $batch->addRecord('www', 'A', '192.168.1.1');

    expect($batch->getOperationsCount())->toBe(1);

    // Mock response for commit
    $response = mockApiResponse(json_encode(['status' => 'SUCCESS', 'id' => '123']));
    $response->shouldReceive('getStatusCode')->andReturn(200);
    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $batch->commit();
    expect($batch->getOperationsCount())->toBe(0);
});

test('dns batch builder can rollback operations', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $dns = new DnsService($mock, $config, 'example.com');

    $batch = new DnsBatchBuilder($dns);
    $batch->addRecord('www', 'A', '192.168.1.1');

    expect($batch->getOperationsCount())->toBe(1);

    $batch->rollback();
    expect($batch->getOperationsCount())->toBe(0);
});

test('dns batch builder is fluent', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $dns = new DnsService($mock, $config, 'example.com');

    $batch = new DnsBatchBuilder($dns);

    $dnsBatchBuilder = $batch
        ->addRecord('www', 'A', '192.168.1.1')
        ->editRecord(123, ['ttl' => '7200'])
        ->deleteRecord(456);

    expect($dnsBatchBuilder)->toBe($batch);
});
