<?php

declare(strict_types=1);

use Porkbun\Client;
use Porkbun\Exception\ApiException;
use Psr\Http\Client\ClientInterface;

test('service integration - dns service full workflow', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $client = new Client(null, $mock);
    $client->setAuth('pk1_key', 'sk1_secret');

    // Mock responses for DNS workflow
    $createResponse = mockApiResponse(json_encode(['status' => 'SUCCESS', 'id' => '123456']));
    $createResponse->shouldReceive('getStatusCode')->andReturn(200);

    $retrieveResponse = mockApiResponse(json_encode([
        'status' => 'SUCCESS',
        'records' => [['id' => '123456', 'name' => 'www', 'type' => 'A', 'content' => '192.168.1.1']],
    ]));
    $retrieveResponse->shouldReceive('getStatusCode')->andReturn(200);

    $editResponse = mockApiResponse(json_encode(['status' => 'SUCCESS']));
    $editResponse->shouldReceive('getStatusCode')->andReturn(200);

    $deleteResponse = mockApiResponse(json_encode(['status' => 'SUCCESS']));
    $deleteResponse->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->times(4)->andReturn(
        $createResponse,
        $retrieveResponse,
        $editResponse,
        $deleteResponse
    );

    $dns = $client->dns('example.com');

    // Create record
    $recordId = $dns->create('www', 'A', '192.168.1.1', 3600);
    expect($recordId)->toBe(123456);

    // Retrieve records
    $records = $dns->retrieve();
    expect($records['status'])->toBe('SUCCESS');

    // Edit record
    $dns->edit(123456, ['content' => '192.168.1.2']);

    // Delete record
    $dns->delete(123456);
});

test('service integration - domain service operations', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $client = new Client(null, $mock);
    $client->setAuth('pk1_key', 'sk1_secret');

    $listResponse = mockApiResponse(json_encode([
        'status' => 'SUCCESS',
        'domains' => [['domain' => 'example.com', 'status' => 'ACTIVE']],
    ]));
    $listResponse->shouldReceive('getStatusCode')->andReturn(200);

    $checkResponse = mockApiResponse(json_encode([
        'status' => 'SUCCESS',
        'available' => false,
    ]));
    $checkResponse->shouldReceive('getStatusCode')->andReturn(200);

    $nsResponse = mockApiResponse(json_encode([
        'status' => 'SUCCESS',
        'ns' => ['ns1.porkbun.com', 'ns2.porkbun.com'],
    ]));
    $nsResponse->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->times(3)->andReturn(
        $listResponse,
        $checkResponse,
        $nsResponse
    );

    $domains = $client->domains();

    // List domains
    $domainList = $domains->listAll();
    expect($domainList['status'])->toBe('SUCCESS');
    expect($domainList['domains'])->toHaveCount(1);

    // Check domain availability
    $availability = $domains->checkDomain('example.com');
    expect($availability['available'])->toBeFalse();

    // Get nameservers
    $nameservers = $domains->getNs('example.com');
    expect($nameservers['ns'])->toHaveCount(2);
});

test('service integration - error handling across services', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $client = new Client(null, $mock);
    $client->setAuth('pk1_key', 'sk1_secret');

    $errorResponse = mockApiResponse(json_encode([
        'status' => 'ERROR',
        'message' => 'Domain not found',
    ]));
    $errorResponse->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($errorResponse);

    expect(fn (): array => $client->domains()->checkDomain('nonexistent.com'))
        ->toThrow(ApiException::class, 'Domain not found');
});

test('service integration - ssl certificate retrieval', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $client = new Client(null, $mock);
    $client->setAuth('pk1_key', 'sk1_secret');

    $sslResponse = mockApiResponse(json_encode([
        'status' => 'SUCCESS',
        'certificatechain' => '-----BEGIN CERTIFICATE-----...',
        'privatekey' => '-----BEGIN PRIVATE KEY-----...',
        'publickey' => '-----BEGIN PUBLIC KEY-----...',
    ]));
    $sslResponse->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($sslResponse);

    $ssl = $client->ssl('example.com');
    $certificate = $ssl->retrieve();

    expect($certificate['status'])->toBe('SUCCESS');
    expect($certificate)->toHaveKeys(['certificatechain', 'privatekey', 'publickey']);
});

test('service integration - auth service ping', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $client = new Client(null, $mock);
    $client->setAuth('pk1_key', 'sk1_secret');

    $pingResponse = mockApiResponse(json_encode([
        'status' => 'SUCCESS',
        'yourIp' => '203.0.113.1',
    ]));
    $pingResponse->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($pingResponse);

    $auth = $client->auth();
    $result = $auth->ping();

    expect($result['status'])->toBe('SUCCESS');
    expect($result['yourIp'])->toBe('203.0.113.1');
});
