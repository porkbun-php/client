<?php

declare(strict_types=1);

use Porkbun\Api\Domains;
use Porkbun\DTO\Domain;
use Porkbun\DTO\DomainCollection;
use Porkbun\DTO\DomainLabel;
use Porkbun\Exception\InvalidArgumentException;

test('domains api can list all domains', function (): void {
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

    $domainCollection = $domains->all();

    $first = $domainCollection->first();

    assert($first instanceof Domain);
    expect($domainCollection)->toBeInstanceOf(DomainCollection::class)
        ->and($domainCollection)->toHaveCount(2)
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

    $domainCollection = $domains->all(start: 0, includeLabels: true);
    $domain = $domainCollection->first();

    assert($domain instanceof Domain);
    expect($domain->domain)->toBe('example.me')
        ->and($domain->tld)->toBe('me')
        ->and($domain->securityLock)->toBeTrue()
        ->and($domain->autoRenew)->toBeFalse()
        ->and($domain->labels)->not->toBeNull()
        ->and($domain->labels)->toHaveCount(1);

    /** @var array<DomainLabel> $labels */
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

    expect($results)->toHaveKey('example.com')
        ->and($results['example.com']['status'])->toBe('SUCCESS');
});

test('domains api rejects empty domain list for auto renew', function (): void {
    $mockClient = createMockHttpClient([]);
    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domains = new Domains(createMockContext($httpClient));

    expect(fn (): array => $domains->enableAutoRenew())->toThrow(InvalidArgumentException::class, 'At least one domain');
    expect(fn (): array => $domains->disableAutoRenew())->toThrow(InvalidArgumentException::class, 'At least one domain');
});

test('allPages iterates across multiple pages', function (): void {
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

    $allDomains = iterator_to_array($domains->allPages());

    expect($allDomains)->toHaveCount(1500)
        ->and($allDomains[0])->toBeInstanceOf(Domain::class)
        ->and($allDomains[0]->domain)->toBe('domain0.com')
        ->and($allDomains[999]->domain)->toBe('domain999.com')
        ->and($allDomains[1000]->domain)->toBe('extra0.com');
});

test('allPages stops on empty first page', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'domains' => []],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domains = new Domains(createMockContext($httpClient));

    $allDomains = iterator_to_array($domains->allPages());

    expect($allDomains)->toHaveCount(0);
});

test('allCollections yields DomainCollection per page', function (): void {
    $page1Domains = [];
    for ($i = 0; $i < 1000; $i++) {
        $page1Domains[] = ['domain' => "domain{$i}.com", 'status' => 'ACTIVE'];
    }

    $page2Domains = [
        ['domain' => 'last.com', 'status' => 'ACTIVE'],
    ];

    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'domains' => $page1Domains],
        ['status' => 'SUCCESS', 'domains' => $page2Domains],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $domains = new Domains(createMockContext($httpClient));

    $collections = iterator_to_array($domains->allCollections());

    expect($collections)->toHaveCount(2)
        ->and($collections[0])->toBeInstanceOf(DomainCollection::class)
        ->and($collections[0])->toHaveCount(1000)
        ->and($collections[1])->toBeInstanceOf(DomainCollection::class)
        ->and($collections[1])->toHaveCount(1);
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
        ->and($results['example1.com']['status'])->toBe('SUCCESS')
        ->and($results['example2.com']['status'])->toBe('SUCCESS');
});
