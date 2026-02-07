<?php

declare(strict_types=1);

use Porkbun\Api\Domain;
use Porkbun\DTO\Domain as DomainDto;
use Porkbun\DTO\DomainCheckData;
use Porkbun\DTO\GlueRecord;
use Porkbun\DTO\UrlForward;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

test('domain api can list all domains', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'domains' => [
                ['domain' => 'example.com', 'status' => 'ACTIVE'],
                ['domain' => 'example.org', 'status' => 'ACTIVE'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domain = new Domain($httpClient);

    $result = $domain->listAll();

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(DomainDto::class)
        ->and($result[0]->domain)->toBe('example.com');
});

test('domain api can check domain availability', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'response' => [
                'avail' => 'yes',
                'type' => 'standard',
                'price' => '8.68',
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domain = new Domain($httpClient);

    $domainCheckData = $domain->check('available-domain.com');

    expect($domainCheckData)->toBeInstanceOf(DomainCheckData::class)
        ->and($domainCheckData->isAvailable)->toBeTrue()
        ->and($domainCheckData->type)->toBe('standard')
        ->and($domainCheckData->price)->toBe(8.68);
});

test('domain api can update nameservers', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/domain/updateNs/example.com');

            $body = json_decode((string) $request->getBody(), true);
            expect($body)->toHaveKey('ns1', 'ns1.provider.com')
                ->and($body)->toHaveKey('ns2', 'ns2.provider.com');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $domain = new Domain($httpClient);

    $domain->updateNameservers('example.com', ['ns1.provider.com', 'ns2.provider.com']);
});

test('domain api can get nameservers', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'ns' => ['ns1.porkbun.com', 'ns2.porkbun.com'],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domain = new Domain($httpClient);

    $result = $domain->getNameservers('example.com');

    expect($result)->toBe(['ns1.porkbun.com', 'ns2.porkbun.com']);
});

test('domain api can add url forward', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/domain/addUrlForward/example.com');

            $body = json_decode((string) $request->getBody(), true);
            expect($body)->toHaveKey('subdomain', 'www')
                ->and($body)->toHaveKey('location', 'https://target.com')
                ->and($body)->toHaveKey('type', 'temporary');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $domain = new Domain($httpClient);

    $domain->addUrlForward('example.com', [
        'subdomain' => 'www',
        'location' => 'https://target.com',
        'type' => 'temporary',
    ]);
});

test('domain api can get url forwards', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'forwards' => [
                ['id' => '1', 'subdomain' => 'www', 'location' => 'https://target.com', 'type' => 'temporary', 'includePath' => 'yes', 'wildcard' => 'no'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domain = new Domain($httpClient);

    $result = $domain->getUrlForwards('example.com');

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(1)
        ->and($result[0])->toBeInstanceOf(UrlForward::class)
        ->and($result[0]->subdomain)->toBe('www');
});

test('domain api can delete url forward', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/domain/deleteUrlForward/example.com/123');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $domain = new Domain($httpClient);

    $domain->deleteUrlForward('example.com', 123);
});

test('domain api can create glue record', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/domain/createGlue/example.com/ns1');

            $body = json_decode((string) $request->getBody(), true);
            expect($body)->toHaveKey('ip1', '192.0.2.1')
                ->and($body)->toHaveKey('ip2', '192.0.2.2');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $domain = new Domain($httpClient);

    $domain->createGlueRecord('example.com', 'ns1', ['192.0.2.1', '192.0.2.2']);
});

test('domain api can update glue record', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/domain/updateGlue/example.com/ns1');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $domain = new Domain($httpClient);

    $domain->updateGlueRecord('example.com', 'ns1', ['192.0.2.3']);
});

test('domain api can delete glue record', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/domain/deleteGlue/example.com/ns1');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $domain = new Domain($httpClient);

    $domain->deleteGlueRecord('example.com', 'ns1');
});

test('domain api can get glue records', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'glue' => [
                ['host' => 'ns1', 'ip' => ['192.0.2.1', '192.0.2.2']],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domain = new Domain($httpClient);

    $result = $domain->getGlueRecords('example.com');

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(1)
        ->and($result[0])->toBeInstanceOf(GlueRecord::class)
        ->and($result[0]->host)->toBe('ns1');
});
