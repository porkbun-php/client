<?php

declare(strict_types=1);

use Porkbun\Api\Dns;
use Porkbun\Api\Domains;
use Porkbun\Api\Nameservers;
use Porkbun\DTO\AvailabilityResult;
use Porkbun\DTO\CreateResult;
use Porkbun\DTO\DnsRecordCollection;
use Porkbun\DTO\DomainCollection;
use Porkbun\DTO\NameserverCollection;
use Porkbun\DTO\PaginatedResult;
use Porkbun\DTO\SslCertificate;
use Porkbun\Exception\ApiException;
use Porkbun\Resource\Domain;

test('service integration - dns service full workflow', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'id' => 123456],
        ['status' => 'SUCCESS', 'records' => [['id' => '123456', 'name' => 'www', 'type' => 'A', 'content' => '192.0.2.1', 'ttl' => '600', 'prio' => '0']]],
        ['status' => 'SUCCESS'],
        ['status' => 'SUCCESS'],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $dns = new Dns(createMockContext($httpClient), 'example.com');

    $createResult = $dns->create('A', 'www', '192.0.2.1', 3600);
    expect($createResult)->toBeInstanceOf(CreateResult::class)
        ->and($createResult->id)->toBe(123456);

    $dnsRecordCollection = $dns->all();
    expect($dnsRecordCollection)->toBeInstanceOf(DnsRecordCollection::class)
        ->and($dnsRecordCollection->isNotEmpty())->toBeTrue();

    $dns->update(123456, 'A', 'www', '192.0.2.2');

    $dns->delete(123456);
});

test('service integration - domains service operations', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'domains' => [['domain' => 'example.com', 'status' => 'ACTIVE']]],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domains = new Domains(createMockContext($httpClient));

    $result = $domains->list();
    $first = $result->domains()->first();

    assert($first instanceof Porkbun\DTO\Domain);
    expect($result)->toBeInstanceOf(PaginatedResult::class)
        ->and($result)->toHaveCount(1)
        ->and($result->domains())->toBeInstanceOf(DomainCollection::class)
        ->and($first->domain)->toBe('example.com');
});

test('service integration - domain check availability', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'response' => ['avail' => 'no', 'type' => 'registration', 'price' => '1.01']],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $clientContext = createMockContext($httpClient);
    $domain = new Domain('example.com', $clientContext, new Domains($clientContext));

    $availability = $domain->check();
    expect($availability)->toBeInstanceOf(AvailabilityResult::class)
        ->and($availability->isAvailable)->toBeFalse();
});

test('service integration - nameservers service operations', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'ns' => ['ns1.porkbun.com', 'ns2.porkbun.com']],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $nameservers = new Nameservers(createMockContext($httpClient), 'example.com');

    $nameserverCollection = $nameservers->all();
    expect($nameserverCollection)->toBeInstanceOf(NameserverCollection::class)
        ->and($nameserverCollection)->toHaveCount(2)
        ->and($nameserverCollection->first())->toBe('ns1.porkbun.com')
        ->and($nameserverCollection->last())->toBe('ns2.porkbun.com');
});

test('service integration - error handling across services', function (): void {
    $mockClient = createMockHttpClient([
        ['body' => ['status' => 'ERROR', 'message' => 'Domain not found'], 'httpStatus' => 200],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $clientContext = createMockContext($httpClient);
    $domain = new Domain('nonexistent.com', $clientContext, new Domains($clientContext));

    expect(fn (): AvailabilityResult => $domain->check())
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
    $context = createMockContext($httpClient);
    $domain = new Domain('example.com', $context, new Domains($context));

    $sslCertificate = $domain->ssl();

    expect($sslCertificate)->toBeInstanceOf(SslCertificate::class)
        ->and($sslCertificate->certificateChain)->toBe('-----BEGIN CERTIFICATE-----\nMIIC...')
        ->and($sslCertificate->privateKey)->toBe('-----BEGIN RSA PRIVATE KEY-----\nMIIE...')
        ->and($sslCertificate->publicKey)->toBe('-----BEGIN PUBLIC KEY-----\nMIIB...');
});
