<?php

declare(strict_types=1);

use Porkbun\Config;
use Porkbun\Service\DnsService;
use Psr\Http\Client\ClientInterface;

test('dns service requires auth', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();

    $service = new DnsService($mock, $config, 'example.com');

    // Use reflection to access protected method
    $reflection = new ReflectionClass($service);
    $reflectionMethod = $reflection->getMethod('requiresAuth');
    $reflectionMethod->setAccessible(true);

    expect($reflectionMethod->invoke($service))->toBeTrue();
});

test('dns service can create record', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = [
        'status' => 'SUCCESS',
        'id' => '123456',
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DnsService($mock, $config, 'example.com');
    $recordId = $service->create('www', 'A', '192.168.1.1');

    expect($recordId)->toBe(123456);
});

test('dns service can retrieve records', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = [
        'status' => 'SUCCESS',
        'records' => [
            ['id' => '123', 'name' => 'www', 'type' => 'A', 'content' => '192.168.1.1'],
        ],
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DnsService($mock, $config, 'example.com');
    $result = $service->retrieve();

    expect($result)->toBe($responseData);
});

test('dns service can retrieve specific record by id', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = [
        'status' => 'SUCCESS',
        'records' => [
            ['id' => '123456', 'name' => 'www', 'type' => 'A', 'content' => '192.168.1.1'],
        ],
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DnsService($mock, $config, 'example.com');
    $result = $service->retrieve(123456);

    expect($result)->toBe($responseData);
});

test('dns service can edit record by id', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = ['status' => 'SUCCESS'];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DnsService($mock, $config, 'example.com');
    $service->edit(123456, ['content' => '192.168.1.2']);

    expect(true)->toBeTrue(); // No exception thrown
});

test('dns service can edit record by name and type without subdomain', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = ['status' => 'SUCCESS'];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DnsService($mock, $config, 'example.com');
    $service->editByNameType('A', null, ['content' => '192.168.1.2']);

    expect(true)->toBeTrue(); // No exception thrown
});

test('dns service can edit record by name and type with subdomain', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = ['status' => 'SUCCESS'];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DnsService($mock, $config, 'example.com');
    $service->editByNameType('A', 'www', ['content' => '192.168.1.2']);

    expect(true)->toBeTrue(); // No exception thrown
});

test('dns service can delete record by id', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = ['status' => 'SUCCESS'];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DnsService($mock, $config, 'example.com');
    $service->delete(123456);

    expect(true)->toBeTrue(); // No exception thrown
});

test('dns service can delete record by name and type without subdomain', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = ['status' => 'SUCCESS'];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DnsService($mock, $config, 'example.com');
    $service->deleteByNameType('A', null);

    expect(true)->toBeTrue(); // No exception thrown
});

test('dns service can delete record by name and type with subdomain', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = ['status' => 'SUCCESS'];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DnsService($mock, $config, 'example.com');
    $service->deleteByNameType('A', 'www');

    expect(true)->toBeTrue(); // No exception thrown
});

test('dns service can retrieve record by name and type without subdomain', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = [
        'status' => 'SUCCESS',
        'records' => [
            ['id' => '123', 'name' => '', 'type' => 'A', 'content' => '192.168.1.1'],
        ],
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DnsService($mock, $config, 'example.com');
    $result = $service->retrieveByNameType('A', null);

    expect($result)->toBe($responseData);
});

test('dns service can retrieve record by name and type with subdomain', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = [
        'status' => 'SUCCESS',
        'records' => [
            ['id' => '123', 'name' => 'www', 'type' => 'A', 'content' => '192.168.1.1'],
        ],
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DnsService($mock, $config, 'example.com');
    $result = $service->retrieveByNameType('A', 'www');

    expect($result)->toBe($responseData);
});

test('dns service can create dnssec record', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = ['status' => 'SUCCESS'];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DnsService($mock, $config, 'example.com');
    $params = [
        'keyTag' => '64087',
        'alg' => '13',
        'digestType' => '2',
        'digest' => 'ABC123...',
    ];
    $service->createDnssecRecord($params);

    expect(true)->toBeTrue(); // No exception thrown
});

test('dns service can get dnssec records', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = [
        'status' => 'SUCCESS',
        'records' => [
            [
                'keyTag' => '64087',
                'alg' => '13',
                'digestType' => '2',
                'digest' => 'ABC123...',
            ],
        ],
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DnsService($mock, $config, 'example.com');
    $result = $service->getDnssecRecords();

    expect($result)->toBe($responseData);
});

test('dns service can delete dnssec record', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = ['status' => 'SUCCESS'];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DnsService($mock, $config, 'example.com');
    $service->deleteDnssecRecord(64087);

    expect(true)->toBeTrue(); // No exception thrown
});

test('dns service create method returns int record id', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = [
        'status' => 'SUCCESS',
        'id' => '123456',
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new DnsService($mock, $config, 'example.com');
    $recordId = $service->create('test', 'A', '192.168.1.1', 3600, 10, 'Test note');

    expect($recordId)->toBe(123456);
    expect($recordId)->toBeInt();
});

test('dns service create method omits empty notes', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = [
        'status' => 'SUCCESS',
        'id' => '123456',
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    // Verify that notes field is not included when empty
    $mock->shouldReceive('sendRequest')
        ->with(Mockery::on(function ($request): bool {
            $body = json_decode($request->getBody()->getContents(), true);

            return is_array($body) && !isset($body['notes']) && $body['name'] === 'test';
        }))
        ->once()
        ->andReturn($response);

    $service = new DnsService($mock, $config, 'example.com');
    $service->create('test', 'A', '192.168.1.1', 600, 0, '');
});

test('dns service converts ttl and prio to strings', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = [
        'status' => 'SUCCESS',
        'id' => '123456',
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    // Verify that ttl and prio are sent as strings
    $mock->shouldReceive('sendRequest')
        ->with(Mockery::on(function ($request): bool {
            $body = json_decode($request->getBody()->getContents(), true);

            return is_array($body) && $body['ttl'] === '3600' && $body['prio'] === '10';
        }))
        ->once()
        ->andReturn($response);

    $service = new DnsService($mock, $config, 'example.com');
    $service->create('test', 'A', '192.168.1.1', 3600, 10);
});
