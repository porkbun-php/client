<?php

declare(strict_types=1);

use Porkbun\Client;
use Porkbun\Middleware\LoggingMiddleware;
use Porkbun\Request\CreateDnsRecordRequest;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

test('integration - dns service with builder pattern', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $client = new Client(null, $mock);
    $client->setAuth('pk1_key', 'sk1_secret');

    $response = mockApiResponse(json_encode(['status' => 'SUCCESS', 'id' => '123456']));
    $response->shouldReceive('getStatusCode')->andReturn(200);
    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $dns = $client->dns('example.com');

    // Test builder pattern
    $dnsRecordBuilder = $dns->record();
    $recordId = $dns->createFromBuilder(
        $dnsRecordBuilder->name('www')->a('192.168.1.1')->ttl(3600)->notes('Web server')
    );

    expect($recordId)->toBe(123456);
});

test('integration - dns service batch operations', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $client = new Client(null, $mock);
    $client->setAuth('pk1_key', 'sk1_secret');

    // Mock responses for batch operations
    $createResponse = mockApiResponse(json_encode(['status' => 'SUCCESS', 'id' => '123']));
    $createResponse->shouldReceive('getStatusCode')->andReturn(200);

    $editResponse = mockApiResponse(json_encode(['status' => 'SUCCESS']));
    $editResponse->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->times(2)->andReturn($createResponse, $editResponse);

    $dns = $client->dns('example.com');

    $results = $dns->batch()
        ->addRecord('www', 'A', '192.168.1.1')
        ->editRecord(456, ['ttl' => '7200'])
        ->commit();

    expect($results)->toHaveCount(2);
    expect($results[0]['status'])->toBe('success');
    expect($results[1]['status'])->toBe('success');
});

test('integration - pricing service with response objects', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $client = new Client(null, $mock);

    $responseData = [
        'status' => 'SUCCESS',
        'pricing' => [
            'com' => ['registration' => '8.68', 'renewal' => '8.68'],
            'net' => ['registration' => '9.98', 'renewal' => '9.98'],
        ],
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);
    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $pricing = $client->pricing();
    $pricingResponse = $pricing->getPricingAsResponse();

    expect($pricingResponse->isSuccess())->toBeTrue();
    expect($pricingResponse->hasDomain('com'))->toBeTrue();
    expect($pricingResponse->getRegistrationPrice('com'))->toBe('8.68');
    expect($pricingResponse->getAllTlds())->toBe(['com', 'net']);
});

test('integration - service with middleware', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $client = new Client(null, $mock);

    $response = mockApiResponse(json_encode(['status' => 'SUCCESS', 'pricing' => []]));
    $response->shouldReceive('getStatusCode')->andReturn(200);
    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('info')->twice(); // before and after

    $pricing = $client->pricing();
    $pricing->addMiddleware(new LoggingMiddleware($logger));

    // Use request/response pattern to trigger middleware
    $pricingResponse = $pricing->getPricingFromRequest();

    expect($pricingResponse->isSuccess())->toBeTrue();
});

test('integration - dns service with typed requests', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $client = new Client(null, $mock);
    $client->setAuth('pk1_key', 'sk1_secret');

    $response = mockApiResponse(json_encode(['status' => 'SUCCESS', 'id' => '789123']));
    $response->shouldReceive('getStatusCode')->andReturn(200);
    $httpRequest = Mockery::mock(RequestInterface::class);
    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $dns = $client->dns('example.com');

    $request = new CreateDnsRecordRequest(
        'example.com',
        'api',
        'A',
        '10.0.0.1',
        7200,
        0,
        'API server'
    );

    $createDnsRecordResponse = $dns->createFromRequest($request);

    expect($createDnsRecordResponse->isSuccess())->toBeTrue();
    expect($createDnsRecordResponse->getId())->toBe(789123);
    expect($createDnsRecordResponse->hasId())->toBeTrue();
});

test('integration - dns service retrieve with response objects', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $client = new Client(null, $mock);
    $client->setAuth('pk1_key', 'sk1_secret');

    $responseData = [
        'status' => 'SUCCESS',
        'records' => [
            ['id' => '123', 'name' => 'www', 'type' => 'A', 'content' => '192.168.1.1'],
            ['id' => '124', 'name' => 'api', 'type' => 'A', 'content' => '10.0.0.1'],
        ],
    ];

    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);
    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $dns = $client->dns('example.com');
    $dnsRecordsResponse = $dns->retrieveAsResponse();

    expect($dnsRecordsResponse->isSuccess())->toBeTrue();
    expect($dnsRecordsResponse->getRecordCount())->toBe(2);
    $record = $dnsRecordsResponse->getRecordById(123);
    expect($record)->not()->toBeNull();
    if ($record !== null) {
        expect($record['name'])->toBe('www');
    }
    expect($dnsRecordsResponse->getRecordsByType('A'))->toHaveCount(2);
});
