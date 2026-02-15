<?php

declare(strict_types=1);

use Porkbun\Api\AutoRenew;
use Porkbun\Api\Dns;
use Porkbun\Api\GlueRecords;
use Porkbun\Api\Nameservers;
use Porkbun\Api\Ssl;
use Porkbun\Api\UrlForwarding;
use Porkbun\DTO\Availability;
use Porkbun\DTO\DomainRegistration;
use Porkbun\Resource\Domain;

test('domain facade returns name', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient);

    $domain = new Domain('example.com', createMockContext($httpClient));

    expect($domain->getName())->toBe('example.com');
});

test('domain facade provides dns service', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient);

    $domain = new Domain('example.com', createMockContext($httpClient));

    expect($domain->dns())->toBeInstanceOf(Dns::class);
});

test('domain facade provides ssl service', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient);

    $domain = new Domain('example.com', createMockContext($httpClient));

    expect($domain->ssl())->toBeInstanceOf(Ssl::class);
});

test('domain facade provides nameservers service', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient);

    $domain = new Domain('example.com', createMockContext($httpClient));

    expect($domain->nameservers())->toBeInstanceOf(Nameservers::class);
});

test('domain facade provides url forwarding service', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient);

    $domain = new Domain('example.com', createMockContext($httpClient));

    expect($domain->urlForwarding())->toBeInstanceOf(UrlForwarding::class);
});

test('domain facade provides glue records service', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient);

    $domain = new Domain('example.com', createMockContext($httpClient));

    expect($domain->glue())->toBeInstanceOf(GlueRecords::class);
});

test('domain facade provides auto renew service', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient);

    $domain = new Domain('example.com', createMockContext($httpClient));

    expect($domain->autoRenew())->toBeInstanceOf(AutoRenew::class);
});

test('domain facade caches service instances', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient);

    $domain = new Domain('example.com', createMockContext($httpClient));

    $dns1 = $domain->dns();
    $dns2 = $domain->dns();
    expect($dns1)->toBe($dns2);

    $ssl1 = $domain->ssl();
    $ssl2 = $domain->ssl();
    expect($ssl1)->toBe($ssl2);

    $nameservers = $domain->nameservers();
    $ns2 = $domain->nameservers();
    expect($nameservers)->toBe($ns2);

    $urlForwarding = $domain->urlForwarding();
    $uf2 = $domain->urlForwarding();
    expect($urlForwarding)->toBe($uf2);

    $glueRecords = $domain->glue();
    $glue2 = $domain->glue();
    expect($glueRecords)->toBe($glue2);

    $autoRenew1 = $domain->autoRenew();
    $autoRenew2 = $domain->autoRenew();
    expect($autoRenew1)->toBe($autoRenew2);
});

test('domain facade can check availability', function (): void {
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
    $domain = new Domain('available-domain.com', createMockContext($httpClient));

    $availability = $domain->check();

    expect($availability)->toBeInstanceOf(Availability::class)
        ->and($availability->isAvailable)->toBeTrue()
        ->and($availability->type)->toBe('standard')
        ->and($availability->price)->toBe(8.68);
});

test('domain facade can register domain', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'domain' => 'newdomain.com',
            'cost' => 868,
            'orderId' => 123456,
            'balance' => 5000,
            'limits' => [
                'attempts' => ['limit' => 1, 'used' => 1],
                'success' => ['limit' => 10, 'used' => 1],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domain = new Domain('newdomain.com', createMockContext($httpClient));

    $domainRegistration = $domain->register(868, [
        'years' => 1,
        'addPrivacy' => true,
    ]);

    expect($domainRegistration)->toBeInstanceOf(DomainRegistration::class)
        ->and($domainRegistration->domain)->toBe('newdomain.com')
        ->and($domainRegistration->cost)->toBe(868)
        ->and($domainRegistration->orderId)->toBe(123456);
});
