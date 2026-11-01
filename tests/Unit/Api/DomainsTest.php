<?php

declare(strict_types=1);

use Porkbun\Api\Domains;
use Porkbun\DTO\AutoRenewResult;
use Porkbun\DTO\Domain;
use Porkbun\DTO\DomainCollection;
use Porkbun\DTO\DomainLabel;
use Porkbun\DTO\PaginatedResult;
use Porkbun\DTO\RegistrationRequirements;
use Porkbun\DTO\TransferRecordCollection;
use Porkbun\Exception\ApiException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

test('domains api can find a domain by name', function (): void {
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
    $domains = new Domains(createMockContext($httpClient));

    $domain = $domains->find('example.com');

    assert($domain instanceof Domain);
    expect($domain)->toBeInstanceOf(Domain::class)
        ->and($domain->domain)->toBe('example.com');
});

test('domains api find is case-insensitive', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'domains' => [
                ['domain' => 'Example.COM', 'status' => 'ACTIVE'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domains = new Domains(createMockContext($httpClient));

    $domain = $domains->find('example.com');

    assert($domain instanceof Domain);
    expect($domain)->toBeInstanceOf(Domain::class)
        ->and($domain->domain)->toBe('Example.COM');
});

test('domains api find returns null for unknown domain', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'domains' => [
                ['domain' => 'example.com', 'status' => 'ACTIVE'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domains = new Domains(createMockContext($httpClient));

    $domain = $domains->find('notfound.com');

    expect($domain)->toBeNull();
});

test('domains api find returns domain on second page', function (): void {
    $page1Domains = [];
    for ($i = 0; $i < 1000; $i++) {
        $page1Domains[] = ['domain' => "domain{$i}.com", 'status' => 'ACTIVE'];
    }

    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'domains' => $page1Domains],
        [
            'status' => 'SUCCESS',
            'domains' => [
                ['domain' => 'target.com', 'status' => 'ACTIVE'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domains = new Domains(createMockContext($httpClient));

    $domain = $domains->find('target.com');

    assert($domain instanceof Domain);
    expect($domain)->toBeInstanceOf(Domain::class)
        ->and($domain->domain)->toBe('target.com');
});

test('domains api find returns null after exhausting all pages', function (): void {
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
    $domains = new Domains(createMockContext($httpClient));

    $domain = $domains->find('nothere.com');

    expect($domain)->toBeNull();
});

test('domains api can list domains for a single page', function (): void {
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
    $domains = new Domains(createMockContext($httpClient));

    $result = $domains->list();

    expect($result)->toBeInstanceOf(PaginatedResult::class);

    $first = $result->domains()->first();

    assert($first instanceof Domain);
    expect($result)->toHaveCount(2)
        ->and($result->domains())->toBeInstanceOf(DomainCollection::class)
        ->and($first)->toBeInstanceOf(Domain::class)
        ->and($first->domain)->toBe('example.com');
});

test('domains api supports pagination and labels', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'domains' => [
                [
                    'domain' => 'example.me',
                    'status' => 'ACTIVE',
                    'tld' => 'me',
                    'createDate' => '2020-03-16 22:28:09',
                    'expireDate' => '2026-03-16 22:28:09',
                    'securityLock' => '1',
                    'whoisPrivacy' => '1',
                    'autoRenew' => 0,
                    'notLocal' => 0,
                    'labels' => [
                        ['id' => '123', 'title' => 'Production', 'color' => '#337ab7'],
                    ],
                ],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domains = new Domains(createMockContext($httpClient));

    $result = $domains->list(start: 0, includeLabels: true);
    $domain = $result->domains()->first();


    assert($domain instanceof Domain);
    expect($domain->domain)->toBe('example.me')
        ->and($domain->tld)->toBe('me')
        ->and($domain->securityLock)->toBeTrue()
        ->and($domain->autoRenew)->toBeFalse()
        ->and($domain->labels)->not->toBeNull()
        ->and($domain->labels)->toHaveCount(1);

    /** @var list<DomainLabel> $labels */
    $labels = $domain->labels;
    expect($labels[0]->title)->toBe('Production')
        ->and($labels[0]->color)->toBe('#337ab7');
});

test('domains api can enable auto renew for single domain', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'results' => [
                'example.com' => [
                    'status' => 'SUCCESS',
                    'message' => 'Auto renew status updated.',
                ],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domains = new Domains(createMockContext($httpClient));

    $results = $domains->enableAutoRenew('example.com');

    expect($results)->toHaveCount(1)
        ->and($results[0])->toBeInstanceOf(AutoRenewResult::class)
        ->and($results[0]->domain)->toBe('example.com')
        ->and($results[0]->success)->toBeTrue()
        ->and($results[0]->message)->toBe('Auto renew status updated.');
});

test('domains api rejects empty domain list for auto renew', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domains = new Domains(createMockContext($httpClient));

    /** @phpstan-ignore arguments.count */
    expect(fn (): array => $domains->enableAutoRenew())->toThrow(TypeError::class);
    /** @phpstan-ignore arguments.count */
    expect(fn (): array => $domains->disableAutoRenew())->toThrow(TypeError::class);
});

test('all iterates across multiple pages', function (): void {
    // Page 1: 1000 domains (full page)
    $page1Domains = [];
    for ($i = 0; $i < 1000; $i++) {
        $page1Domains[] = ['domain' => "domain{$i}.com", 'status' => 'ACTIVE'];
    }

    // Page 2: 500 domains (partial page, signals end)
    $page2Domains = [];
    for ($i = 0; $i < 500; $i++) {
        $page2Domains[] = ['domain' => "extra{$i}.com", 'status' => 'ACTIVE'];
    }

    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'domains' => $page1Domains],
        ['status' => 'SUCCESS', 'domains' => $page2Domains],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domains = new Domains(createMockContext($httpClient));

    $allDomains = iterator_to_array($domains->all());

    expect($allDomains)->toHaveCount(1500)
        ->and($allDomains[0])->toBeInstanceOf(Domain::class)
        ->and($allDomains[0]->domain)->toBe('domain0.com')
        ->and($allDomains[999]->domain)->toBe('domain999.com')
        ->and($allDomains[1000]->domain)->toBe('extra0.com');
});

test('all stops on empty first page', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'domains' => []],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domains = new Domains(createMockContext($httpClient));

    $allDomains = iterator_to_array($domains->all());

    expect($allDomains)->toHaveCount(0);
});

test('domains api can disable auto renew for multiple domains', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'results' => [
                'example1.com' => ['status' => 'SUCCESS', 'message' => 'Auto renew status updated.'],
                'example2.com' => ['status' => 'SUCCESS', 'message' => 'Auto renew status updated.'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domains = new Domains(createMockContext($httpClient));

    $results = $domains->disableAutoRenew('example1.com', 'example2.com');

    expect($results)->toHaveCount(2)
        ->and($results[0])->toBeInstanceOf(AutoRenewResult::class)
        ->and($results[0]->domain)->toBe('example1.com')
        ->and($results[0]->success)->toBeTrue()
        ->and($results[1]->domain)->toBe('example2.com')
        ->and($results[1]->success)->toBeTrue();
});

test('domains api gets registration requirements for a tld', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect($request->getMethod())->toBe('GET')
                ->and((string) $request->getUri())->toBe('https://api.porkbun.com/api/json/v3/domain/getRegistrationRequirements/com')
                ->and($request->getHeaderLine('X-API-Key'))->toBe('pk1_key')
                ->and($request->getHeaderLine('X-Secret-API-Key'))->toBe('sk1_secret');

            return true;
        }))
        ->andReturn(createMockResponse('{"status":"SUCCESS","tld":"com","apiRegisterable":true,"registrationDurationYears":1,"maxRegistrationYears":null,"whoisPrivacySupported":true,"requiresValidatedAddress":false,"registrantOnly":false,"requestSchema":{"$schema":"https://json-schema.org/draft/2020-12/schema","title":"domain/create request for .com","description":"Body for POST /api/json/v3/domain/create/{domain}.","type":"object","required":["apikey","secretapikey","cost","agreeToTerms"],"additionalProperties":false,"properties":{"apikey":{"type":"string","description":"Public API key (pk1_...)."},"secretapikey":{"type":"string","description":"Secret API key (sk1_...)."},"cost":{"type":"integer","minimum":0,"description":"Total price in US cents (integer)."},"agreeToTerms":{"type":"string","enum":["yes","1"],"description":"Must be \\"yes\\" to accept the registration agreement, ToS, Data Processing Addendum, privacy policy, and auto-renew terms."},"whoisPrivacy":{"type":"string","enum":["yes","no"],"description":"Optional."}}},"registryRequirements":null,"requestId":"00000000-0000-0000-0000-000000000000","sandbox":true}'));

    $domains = new Domains(createMockContext(createHttpClient($mock, 'pk1_key', 'sk1_secret')));
    $requirements = $domains->registrationRequirements('com');

    expect($requirements)->toBeInstanceOf(RegistrationRequirements::class)
        ->and($requirements->tld)->toBe('com')
        ->and($requirements->apiRegisterable)->toBeTrue()
        ->and($requirements->registrationDurationYears)->toBe(1)
        ->and($requirements->maxRegistrationYears)->toBeNull()
        ->and($requirements->whoisPrivacySupported)->toBeTrue()
        ->and($requirements->requiresValidatedAddress)->toBeFalse()
        ->and($requirements->registrantOnly)->toBeFalse()
        ->and($requirements->requestSchema['title'])->toBe('domain/create request for .com')
        ->and($requirements->registryRequirements)->toBeNull()
        ->and($requirements->notApiRegisterableReason)->toBeNull();
});

test('domains api registration requirements throws for an unknown tld', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->once()->andReturn(createMockResponse('{"status":"ERROR","message":"Unknown or unsupported TLD.","code":"INVALID_INPUT","next_action":{"type":"fix_request","hint":"Check the request parameters, types, and required fields against the endpoint reference at https://porkbun.com/llms.","retryable":false},"requestId":"00000000-0000-0000-0000-000000000000","sandbox":true}', 400));

    $domains = new Domains(createMockContext(createHttpClient($mock, 'pk1_key', 'sk1_secret')));
    $e = catchPorkbunException(fn (): RegistrationRequirements => $domains->registrationRequirements('notatld'));

    expect($e)->toBeInstanceOf(ApiException::class)
        ->and($e->getMessage())->toBe('Unknown or unsupported TLD.')
        ->and($e->getStatusCode())->toBe(400)
        ->and($e->getErrorCode())->toBe('INVALID_INPUT');
});

test('domains api registration requirements throws when the response has no requirements', function (): void {
    $httpClient = createHttpClient(createMockHttpClient([['status' => 'SUCCESS', 'tld' => 'com']]), 'pk1_key', 'sk1_secret');
    $domains = new Domains(createMockContext($httpClient));

    expect(fn (): RegistrationRequirements => $domains->registrationRequirements('com'))
        ->toThrow(ApiException::class, 'Invalid API response: missing registration requirements');
});

test('domains api lists active transfers', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect($request->getMethod())->toBe('GET')
                ->and((string) $request->getUri())->toBe('https://api.porkbun.com/api/json/v3/domain/listTransfers')
                ->and($request->getHeaderLine('X-API-Key'))->toBe('pk1_key')
                ->and($request->getHeaderLine('X-Secret-API-Key'))->toBe('sk1_secret');

            return true;
        }))
        ->andReturn(createMockResponse('{"status":"SUCCESS","transfers":[{"domain":"example.com","status":"PENDINGTRANSFER","statusDescription":"Waiting for the losing registrar.","transferDate":"2026-01-01T00:00:00Z","orderId":9915398}]}'));

    $domains = new Domains(createMockContext(createHttpClient($mock, 'pk1_key', 'sk1_secret')));

    $transfers = $domains->transfers();

    expect($transfers)->toBeInstanceOf(TransferRecordCollection::class)
        ->and($transfers)->toHaveCount(1)
        ->and($transfers->first()?->domain)->toBe('example.com')
        ->and($transfers->first()?->status)->toBe('PENDINGTRANSFER')
        ->and($transfers->first()?->orderId)->toBe(9915398);
});

test('domains api lists no transfers', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->once()->andReturn(createMockResponse('{"status":"SUCCESS","transfers":[],"requestId":"00000000-0000-0000-0000-000000000000","sandbox":true}'));

    $domains = new Domains(createMockContext(createHttpClient($mock, 'pk1_key', 'sk1_secret')));

    expect($domains->transfers()->isEmpty())->toBeTrue();
});

test('domains api transfers throws when the response has no transfers', function (): void {
    $httpClient = createHttpClient(createMockHttpClient([['status' => 'SUCCESS']]), 'pk1_key', 'sk1_secret');
    $domains = new Domains(createMockContext($httpClient));

    expect(fn (): TransferRecordCollection => $domains->transfers())
        ->toThrow(ApiException::class, 'Invalid API response: missing transfers');
});
