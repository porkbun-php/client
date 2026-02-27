<?php

declare(strict_types=1);

use Porkbun\DTO\Domain;
use Porkbun\DTO\DomainLabel;

test('it creates domain from array', function (): void {
    $domainDTO = Domain::fromArray([
        'domain' => 'example.com',
        'status' => 'ACTIVE',
        'tld' => 'com',
        'createDate' => '2020-01-15 10:30:00',
        'expireDate' => '2025-01-15 10:30:00',
        'securityLock' => '1',
        'whoisPrivacy' => '1',
        'autoRenew' => '0',
        'notLocal' => '0',
    ]);

    expect($domainDTO->domain)->toBe('example.com')
        ->and($domainDTO->status)->toBe('ACTIVE')
        ->and($domainDTO->tld)->toBe('com')
        ->and($domainDTO->createDate)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($domainDTO->expireDate)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($domainDTO->securityLock)->toBeTrue()
        ->and($domainDTO->whoisPrivacy)->toBeTrue()
        ->and($domainDTO->autoRenew)->toBeFalse()
        ->and($domainDTO->notLocal)->toBeFalse();
});

test('it handles mixed int and string boolean values', function (): void {
    $domainDTO = Domain::fromArray([
        'domain' => 'mynk.sh',
        'status' => 'ACTIVE',
        'securityLock' => '1',
        'whoisPrivacy' => '1',
        'autoRenew' => '1',
        'notLocal' => 0,
    ]);

    expect($domainDTO->securityLock)->toBeTrue()
        ->and($domainDTO->whoisPrivacy)->toBeTrue()
        ->and($domainDTO->autoRenew)->toBeTrue()
        ->and($domainDTO->notLocal)->toBeFalse();
});

test('it parses boolean flags strictly', function (): void {
    $domain = Domain::fromArray([
        'domain' => 'a.com', 'status' => 'ACTIVE',
        'securityLock' => '1', 'whoisPrivacy' => 1, 'autoRenew' => true, 'notLocal' => 'yes',
    ]);

    expect($domain->securityLock)->toBeTrue()
        ->and($domain->whoisPrivacy)->toBeTrue()
        ->and($domain->autoRenew)->toBeTrue()
        ->and($domain->notLocal)->toBeTrue();

    $falsy = Domain::fromArray([
        'domain' => 'b.com', 'status' => 'ACTIVE',
        'securityLock' => '0', 'whoisPrivacy' => 0, 'autoRenew' => false, 'notLocal' => 'no',
    ]);

    expect($falsy->securityLock)->toBeFalse()
        ->and($falsy->whoisPrivacy)->toBeFalse()
        ->and($falsy->autoRenew)->toBeFalse()
        ->and($falsy->notLocal)->toBeFalse();

    $edgeCases = Domain::fromArray([
        'domain' => 'c.com', 'status' => 'ACTIVE',
        'securityLock' => 'false', 'whoisPrivacy' => 'true', 'autoRenew' => '', 'notLocal' => '2',
    ]);

    expect($edgeCases->securityLock)->toBeFalse()
        ->and($edgeCases->whoisPrivacy)->toBeFalse()
        ->and($edgeCases->autoRenew)->toBeFalse()
        ->and($edgeCases->notLocal)->toBeFalse();
});

test('it handles invalid date formats gracefully', function (): void {
    $domainDTO = Domain::fromArray([
        'domain' => 'example.com',
        'status' => 'ACTIVE',
        'createDate' => 'invalid-date',
        'expireDate' => 'also-invalid',
    ]);

    expect($domainDTO->createDate)->toBeNull()
        ->and($domainDTO->expireDate)->toBeNull();
});

test('it handles empty dates', function (): void {
    $domainDTO = Domain::fromArray([
        'domain' => 'example.com',
        'status' => 'ACTIVE',
        'createDate' => '',
        'expireDate' => '',
    ]);

    expect($domainDTO->createDate)->toBeNull()
        ->and($domainDTO->expireDate)->toBeNull();
});

test('it parses labels', function (): void {
    $domainDTO = Domain::fromArray([
        'domain' => 'example.com',
        'status' => 'ACTIVE',
        'labels' => [
            ['id' => '1', 'title' => 'Production', 'color' => '#FF0000'],
            ['id' => '2', 'title' => 'Critical', 'color' => '#00FF00'],
        ],
    ]);

    expect($domainDTO->labels)->toBeArray()
        ->and($domainDTO->labels)->toHaveCount(2);

    /** @var array<DomainLabel> $labels */
    $labels = $domainDTO->labels;
    expect($labels[0])->toBeInstanceOf(DomainLabel::class)
        ->and($labels[0]->title)->toBe('Production');
});

test('toArray serializes all fields correctly', function (): void {
    $domainDTO = Domain::fromArray([
        'domain' => 'example.com',
        'status' => 'ACTIVE',
        'tld' => 'com',
        'createDate' => '2020-01-15 10:30:00',
        'expireDate' => '2025-01-15 10:30:00',
        'securityLock' => '1',
        'whoisPrivacy' => '1',
        'autoRenew' => '0',
        'notLocal' => '1',
        'labels' => [
            ['id' => '1', 'title' => 'Test', 'color' => '#000000'],
        ],
    ]);

    $array = $domainDTO->toArray();

    expect($array['domain'])->toBe('example.com')
        ->and($array['status'])->toBe('ACTIVE')
        ->and($array['tld'])->toBe('com')
        ->and($array['createDate'])->toBe('2020-01-15 10:30:00')
        ->and($array['expireDate'])->toBe('2025-01-15 10:30:00')
        ->and($array['securityLock'])->toBe('1')
        ->and($array['whoisPrivacy'])->toBe('1')
        ->and($array['autoRenew'])->toBe('0')
        ->and($array['notLocal'])->toBe('1')
        ->and($array['labels'])->toBeArray()
        ->and($array['labels'][0]['title'])->toBe('Test');
});

test('toArray omits null optional fields', function (): void {
    $domainDTO = Domain::fromArray([
        'domain' => 'example.com',
        'status' => 'ACTIVE',
    ]);

    $array = $domainDTO->toArray();

    expect($array)->toBe([
        'domain' => 'example.com',
        'status' => 'ACTIVE',
        'tld' => 'com',
    ]);
});

test('isExpiringSoon detects expiring domains', function (): void {
    $expiringSoon = new Domain(
        domain: 'example.com',
        status: 'ACTIVE',
        expireDate: new DateTimeImmutable('+15 days'),
    );
    $notExpiring = new Domain(
        domain: 'example.com',
        status: 'ACTIVE',
        expireDate: new DateTimeImmutable('+60 days'),
    );
    $noExpiry = new Domain(
        domain: 'example.com',
        status: 'ACTIVE',
    );

    expect($expiringSoon->isExpiringSoon())->toBeTrue()
        ->and($notExpiring->isExpiringSoon())->toBeFalse()
        ->and($noExpiry->isExpiringSoon())->toBeFalse();
});

test('isExpiringSoon with custom threshold', function (): void {
    $domain = new Domain(
        domain: 'example.com',
        status: 'ACTIVE',
        expireDate: new DateTimeImmutable('+45 days'),
    );

    expect($domain->isExpiringSoon(30))->toBeFalse()
        ->and($domain->isExpiringSoon(60))->toBeTrue();
});

test('tld is explicit or extracted from domain', function (): void {
    $withTld = new Domain(
        domain: 'example.com',
        status: 'ACTIVE',
        tld: 'com',
    );
    $withoutTld = new Domain(
        domain: 'example.co.uk',
        status: 'ACTIVE',
    );

    expect($withTld->tld)->toBe('com')
        ->and($withoutTld->tld)->toBe('uk');
});
