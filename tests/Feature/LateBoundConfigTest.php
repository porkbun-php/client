<?php

declare(strict_types=1);

use Porkbun\Client;
use Porkbun\DTO\DnsRecordCollection;
use Porkbun\DTO\PingResult;
use Porkbun\Enum\Endpoint;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

test('cached domain uses credentials added after creation', function (): void {
    $callCount = 0;
    $capturedRequests = [];

    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->twice()
        ->andReturnUsing(function (RequestInterface $request) use (&$callCount, &$capturedRequests): ResponseInterface {
            $callCount++;
            $capturedRequests[] = $request;

            $body = json_decode((string) $request->getBody(), true);

            if ($callCount === 1) {
                expect($body)->not->toHaveKey('apikey');
                expect($body)->not->toHaveKey('secretapikey');

                return createMockResponse(json_encode([
                    'status' => 'SUCCESS',
                    'records' => [],
                ]));
            }

            expect($body)->toHaveKey('apikey', 'pk1_new_key');
            expect($body)->toHaveKey('secretapikey', 'sk1_new_secret');

            return createMockResponse(json_encode([
                'status' => 'SUCCESS',
                'records' => [
                    ['id' => '1', 'name' => 'www.example.com', 'type' => 'A', 'content' => '192.0.2.1', 'ttl' => '600', 'prio' => '0'],
                ],
            ]));
        });

    $client = new Client($mock);

    $domain = $client->domain('example.com');
    $dns = $domain->dns();

    $records = $dns->all();
    expect($records)->toBeInstanceOf(DnsRecordCollection::class);
    expect($records->isEmpty())->toBeTrue();

    $client->authenticate('pk1_new_key', 'sk1_new_secret');

    $records = $dns->all();
    expect($records)->toBeInstanceOf(DnsRecordCollection::class);
    expect($records->count())->toBe(1);
});

test('cached domain uses endpoint changed after creation', function (): void {
    $callCount = 0;
    $capturedUrls = [];

    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->twice()
        ->andReturnUsing(function (RequestInterface $request) use (&$callCount, &$capturedUrls): ResponseInterface {
            $callCount++;
            $capturedUrls[] = (string) $request->getUri();

            return createMockResponse(json_encode([
                'status' => 'SUCCESS',
                'records' => [],
            ]));
        });

    $client = new Client($mock);
    $client->authenticate('pk1_key', 'sk1_secret');

    $domain = $client->domain('example.com');

    $domain->dns()->all();
    expect($capturedUrls[0])->toContain('api.porkbun.com');

    $client->useIpv4Endpoint();

    $domain->dns()->all();
    expect($capturedUrls[1])->toContain('api-ipv4.porkbun.com');
});

test('ping uses current credentials after authenticate call', function (): void {
    $callCount = 0;

    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->andReturnUsing(function (RequestInterface $request) use (&$callCount): ResponseInterface {
            $callCount++;
            $body = json_decode((string) $request->getBody(), true);

            expect($body)->toHaveKey('apikey', 'pk1_key');
            expect($body)->toHaveKey('secretapikey', 'sk1_secret');

            return createMockResponse(json_encode([
                'status' => 'SUCCESS',
                'yourIp' => '203.0.113.42',
            ]));
        });

    $client = new Client($mock);

    $client->authenticate('pk1_key', 'sk1_secret');
    $pingResult = $client->ping();

    expect($pingResult)->toBeInstanceOf(PingResult::class);
    expect($pingResult->resolvedIp)->toBe('203.0.113.42');
});

test('lastResponse resets to null after config change', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')
        ->twice()
        ->andReturn(createMockResponse(json_encode([
            'status' => 'SUCCESS', 'records' => [],
        ])));

    $client = new Client($mock);
    $client->authenticate('pk1_key', 'sk1_secret');

    $dns = $client->domain('example.com')->dns();

    expect($dns->lastResponse())->toBeNull();

    $dns->all();
    expect($dns->lastResponse())->not->toBeNull();

    $client->useIpv4Endpoint();
    expect($dns->lastResponse())->toBeNull();

    $dns->all();
    expect($dns->lastResponse())->not->toBeNull();
});

test('services cache preserves late-binding behavior', function (): void {
    $capturedUrls = [];

    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->times(3)
        ->andReturnUsing(function (RequestInterface $request) use (&$capturedUrls): ResponseInterface {
            $capturedUrls[] = (string) $request->getUri();

            return createMockResponse(json_encode([
                'status' => 'SUCCESS',
                'records' => [],
            ]));
        });

    $client = new Client($mock);
    $client->authenticate('pk1_key', 'sk1_secret');

    $domain = $client->domain('example.com');

    $dns1 = $domain->dns();
    $dns2 = $domain->dns();

    expect($dns1)->toBe($dns2);

    $dns1->all();
    expect($capturedUrls[0])->toContain(Endpoint::DEFAULT->value);

    $client->useIpv4Endpoint();
    $dns2->all();
    expect($capturedUrls[1])->toContain(Endpoint::IPV4->value);

    $client->useDefaultEndpoint();
    $dns1->all();
    expect($capturedUrls[2])->toContain(Endpoint::DEFAULT->value);
});
