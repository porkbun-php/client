<?php

declare(strict_types=1);

use Porkbun\Api\Dns;
use Porkbun\Api\Domain;
use Porkbun\Api\Ping;
use Porkbun\Api\Ssl;
use Porkbun\DTO\CreateDnsRecordData;
use Porkbun\DTO\DnsRecordCollection;
use Porkbun\DTO\DomainCheckData;
use Porkbun\DTO\PingData;
use Porkbun\DTO\SslCertificate;
use Porkbun\Exception\ApiException;

test('service integration - dns service full workflow', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 123456],
        ['status' => 'SUCCESS', 'records' => [['id' => '123456', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1', 'ttl' => '600', 'prio' => '0']]],
        ['status' => 'SUCCESS'],
        ['status' => 'SUCCESS'],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns($httpClient, 'example.com');

    $createDnsRecordData = $dns->create('www', 'A', '192.0.2.1', 3600);
    expect($createDnsRecordData)->toBeInstanceOf(CreateDnsRecordData::class)
        ->and($createDnsRecordData->id)->toBe(123456);

    $dnsRecordCollection = $dns->retrieve();
    expect($dnsRecordCollection)->toBeInstanceOf(DnsRecordCollection::class)
        ->and($dnsRecordCollection->isNotEmpty())->toBeTrue();

    $dns->edit(123456, ['content' => '192.0.2.2']);

    $dns->delete(123456);
});

test('service integration - domain service operations', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'domains' => [['domain' => 'example.com', 'status' => 'ACTIVE']]],
        ['status' => 'SUCCESS', 'response' => ['avail' => 'no', 'type' => 'registration', 'price' => '1.01']],
        ['status' => 'SUCCESS', 'ns' => ['ns1.porkbun.com', 'ns2.porkbun.com']],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domain = new Domain($httpClient);

    $domains = $domain->listAll();
    expect($domains)->toBeArray()
        ->and($domains)->toHaveCount(1)
        ->and($domains[0]->domain)->toBe('example.com');

    $domainCheckData = $domain->check('example.com');
    expect($domainCheckData)->toBeInstanceOf(DomainCheckData::class)
        ->and($domainCheckData->isAvailable)->toBeFalse();

    $nameservers = $domain->getNameservers('example.com');
    expect($nameservers)->toBe(['ns1.porkbun.com', 'ns2.porkbun.com']);
});

test('service integration - error handling across services', function (): void {
    $mockClient = createMockHttpClient([
        ['body' => ['status' => 'ERROR', 'message' => 'Domain not found'], 'httpStatus' => 200],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domain = new Domain($httpClient);

    expect(fn (): DomainCheckData => $domain->check('nonexistent.com'))
        ->toThrow(ApiException::class, 'Domain not found');
});

test('service integration - ssl certificate retrieval', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'certificatechain' => '-----BEGIN CERTIFICATE-----\nMIIC...',
            'privatekey' => '-----BEGIN RSA PRIVATE KEY-----\nMIIE...',
            'publickey' => '-----BEGIN PUBLIC KEY-----\nMIIB...',
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $ssl = new Ssl($httpClient, 'example.com');

    $sslCertificate = $ssl->retrieve();

    expect($sslCertificate)->toBeInstanceOf(SslCertificate::class)
        ->and($sslCertificate->certificateChain)->toBe('-----BEGIN CERTIFICATE-----\nMIIC...')
        ->and($sslCertificate->privateKey)->toBe('-----BEGIN RSA PRIVATE KEY-----\nMIIE...')
        ->and($sslCertificate->publicKey)->toBe('-----BEGIN PUBLIC KEY-----\nMIIB...');
});

test('service integration - ping service authentication test', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'yourIp' => '203.0.113.1'],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $ping = new Ping($httpClient);

    $pingData = $ping->check();

    expect($pingData)->toBeInstanceOf(PingData::class)
        ->and($pingData->yourIp)->toBe('203.0.113.1');
});
