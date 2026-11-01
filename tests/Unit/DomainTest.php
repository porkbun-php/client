<?php

declare(strict_types=1);

use Porkbun\Api\AutoRenew;
use Porkbun\Api\Contacts;
use Porkbun\Api\Dns;
use Porkbun\Api\Dnssec;
use Porkbun\Api\GlueRecords;
use Porkbun\Api\Nameservers;
use Porkbun\Api\Transfer;
use Porkbun\Api\UrlForwarding;
use Porkbun\DTO\AvailabilityResult;
use Porkbun\DTO\Domain as DomainDto;
use Porkbun\DTO\DomainLabel;
use Porkbun\DTO\DomainRegistration;
use Porkbun\DTO\DomainRenewal;
use Porkbun\DTO\SslCertificate;
use Porkbun\Exception\ApiException;
use Porkbun\HttpClient;
use Porkbun\Resource\Domain;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

function createDomainFacade(string $name, HttpClient $httpClient): Domain
{
    return new Domain($name, createMockContext($httpClient));
}

test('domain facade returns name', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient);

    $domain = createDomainFacade('example.com', $httpClient);

    expect($domain->name)->toBe('example.com');
});

test('domain facade provides dns service', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient);

    $domain = createDomainFacade('example.com', $httpClient);

    expect($domain->dns())->toBeInstanceOf(Dns::class);
});

test('domain facade provides dnssec service', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient);

    $domain = createDomainFacade('example.com', $httpClient);

    expect($domain->dnssec())->toBeInstanceOf(Dnssec::class);
});

test('domain facade provides ssl certificate', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'certificatechain' => '-----BEGIN CERTIFICATE-----\nMIID...\n-----END CERTIFICATE-----',
            'privatekey' => '-----BEGIN PRIVATE KEY-----\nMIIE...\n-----END PRIVATE KEY-----',
            'publickey' => '-----BEGIN PUBLIC KEY-----\nMIIB...\n-----END PUBLIC KEY-----',
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domain = createDomainFacade('example.com', $httpClient);

    expect($domain->ssl())->toBeInstanceOf(SslCertificate::class);
});

test('domain facade provides nameservers service', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient);

    $domain = createDomainFacade('example.com', $httpClient);

    expect($domain->nameservers())->toBeInstanceOf(Nameservers::class);
});

test('domain facade provides url forwarding service', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient);

    $domain = createDomainFacade('example.com', $httpClient);

    expect($domain->urlForwarding())->toBeInstanceOf(UrlForwarding::class);
});

test('domain facade provides glue records service', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient);

    $domain = createDomainFacade('example.com', $httpClient);

    expect($domain->glueRecords())->toBeInstanceOf(GlueRecords::class);
});

test('domain facade provides auto renew service', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient);

    $domain = createDomainFacade('example.com', $httpClient);

    expect($domain->autoRenew())->toBeInstanceOf(AutoRenew::class);
});

test('domain facade provides contacts service', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient);

    $domain = createDomainFacade('example.com', $httpClient);

    expect($domain->contacts())->toBeInstanceOf(Contacts::class);
});

test('domain facade provides transfer service', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient);

    $domain = createDomainFacade('example.com', $httpClient);

    expect($domain->transfer())->toBeInstanceOf(Transfer::class);
});

test('domain facade caches service instances', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient);

    $domain = createDomainFacade('example.com', $httpClient);

    $dns1 = $domain->dns();
    $dns2 = $domain->dns();
    expect($dns1)->toBe($dns2);

    $dnssec1 = $domain->dnssec();
    $dnssec2 = $domain->dnssec();
    expect($dnssec1)->toBe($dnssec2);

    $nameservers = $domain->nameservers();
    $ns2 = $domain->nameservers();
    expect($nameservers)->toBe($ns2);

    $urlForwarding = $domain->urlForwarding();
    $uf2 = $domain->urlForwarding();
    expect($urlForwarding)->toBe($uf2);

    $glueRecords = $domain->glueRecords();
    $glue2 = $domain->glueRecords();
    expect($glueRecords)->toBe($glue2);

    $autoRenew1 = $domain->autoRenew();
    $autoRenew2 = $domain->autoRenew();
    expect($autoRenew1)->toBe($autoRenew2);

    $contacts1 = $domain->contacts();
    $contacts2 = $domain->contacts();
    expect($contacts1)->toBe($contacts2);

    $transfer1 = $domain->transfer();
    $transfer2 = $domain->transfer();
    expect($transfer1)->toBe($transfer2);
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
    $domain = createDomainFacade('available-domain.com', $httpClient);

    $availability = $domain->check();

    expect($availability)->toBeInstanceOf(AvailabilityResult::class)
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
    $domain = createDomainFacade('newdomain.com', $httpClient);

    $domainRegistration = $domain->register(868);

    expect($domainRegistration)->toBeInstanceOf(DomainRegistration::class)
        ->and($domainRegistration->domain)->toBe('newdomain.com')
        ->and($domainRegistration->costInCents)->toBe(868)
        ->and($domainRegistration->orderId)->toBe(123456);
});

test('domain facade can renew domain', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'domain' => 'example.com',
            'expirationDate' => '2028-10-30 10:41:05',
            'cost' => 1108,
            'orderId' => 123456,
            'balance' => 5000,
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domain = createDomainFacade('example.com', $httpClient);

    $domainRenewal = $domain->renew(1108);

    expect($domainRenewal)->toBeInstanceOf(DomainRenewal::class)
        ->and($domainRenewal->domain)->toBe('example.com')
        ->and($domainRenewal->costInCents)->toBe(1108)
        ->and($domainRenewal->orderId)->toBe(123456);
});

test('domain facade details gets the domain', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect($request->getMethod())->toBe('GET')
                ->and((string) $request->getUri())->toBe('https://api.porkbun.com/api/json/v3/domain/get/example.com');

            return true;
        }))
        ->andReturn(createMockResponse('{"status":"SUCCESS","domain":{"domain":"example.com","status":"ACTIVE","tld":"com","createDate":"2026-10-30 10:41:05","addedDate":"2026-10-30 10:41:05","expireDate":"2037-10-30 10:41:05","securityLock":0,"whoisPrivacy":1,"autoRenew":1,"apiAccess":1,"notLocal":0},"requestId":"00000000-0000-0000-0000-000000000000","sandbox":true}'));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $domain = createDomainFacade('example.com', $httpClient);

    $details = $domain->details();

    expect($details)->toBeInstanceOf(DomainDto::class)
        ->and($details->domain)->toBe('example.com')
        ->and($details->status)->toBe('ACTIVE')
        ->and($details->tld)->toBe('com')
        ->and($details->expireDate?->format('Y-m-d'))->toBe('2037-10-30')
        ->and($details->securityLock)->toBeFalse()
        ->and($details->whoisPrivacy)->toBeTrue()
        ->and($details->autoRenew)->toBeTrue()
        ->and($details->isExternal)->toBeFalse()
        ->and($details->labels)->toBeNull();
});

test('domain facade details asks for labels', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toBe('https://api.porkbun.com/api/json/v3/domain/get/example.com?includeLabels=yes');

            return true;
        }))
        ->andReturn(createMockResponse('{"status":"SUCCESS","domain":{"domain":"example.com","status":"ACTIVE","tld":"com","createDate":"2021-01-15 10:00:00","addedDate":"2026-10-16 07:06:17","expireDate":"2027-01-15 10:00:00","securityLock":1,"whoisPrivacy":1,"autoRenew":1,"apiAccess":1,"notLocal":0,"labels":[{"id":"123","title":"Production","color":"#337ab7"}]}}'));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $details = createDomainFacade('example.com', $httpClient)->details(includeLabels: true);

    expect($details->labels)->toHaveCount(1);

    /** @var list<DomainLabel> $labels */
    $labels = $details->labels;
    expect($labels[0]->id)->toBe(123)
        ->and($labels[0]->title)->toBe('Production')
        ->and($labels[0]->color)->toBe('#337ab7');
});

test('domain facade details throws when the response has no domain', function (): void {
    $mockClient = createMockHttpClient([['status' => 'SUCCESS']]);
    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');

    expect(fn (): DomainDto => createDomainFacade('example.com', $httpClient)->details())
        ->toThrow(ApiException::class, 'Invalid API response: missing domain');
});

test('domain facade details throws when domain not found', function (): void {
    $mockClient = createMockHttpClient([[
        'httpStatus' => 404,
        'body' => [
            'status' => 'ERROR',
            'message' => 'Domain not found in your account. (Account: sandbox-user.)',
            'code' => 'DOMAIN_NOT_FOUND',
            'account' => 'sandbox-user',
            'requestId' => '00000000-0000-0000-0000-000000000000',
            'sandbox' => true,
        ],
    ]]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $e = catchPorkbunException(fn (): DomainDto => createDomainFacade('notfound.com', $httpClient)->details());

    expect($e)->toBeInstanceOf(ApiException::class)
        ->and($e->getStatusCode())->toBe(404)
        ->and($e->getErrorCode())->toBe('DOMAIN_NOT_FOUND');
});
