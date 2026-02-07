<?php

declare(strict_types=1);

use Porkbun\DTO\Domain;
use Porkbun\DTO\DomainLabel;

test('it creates domain from array', function (): void {
    $domain = Domain::fromArray([
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

    expect($domain->domain)->toBe('example.com')
        ->and($domain->status)->toBe('ACTIVE')
        ->and($domain->tld)->toBe('com')
        ->and($domain->createDate)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($domain->expireDate)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($domain->securityLock)->toBeTrue()
        ->and($domain->whoisPrivacy)->toBeTrue()
        ->and($domain->autoRenew)->toBeFalse()
        ->and($domain->notLocal)->toBeFalse();
});

test('it handles invalid date formats gracefully', function (): void {
    $domain = Domain::fromArray([
        'domain' => 'example.com',
        'status' => 'ACTIVE',
        'createDate' => 'invalid-date',
        'expireDate' => 'also-invalid',
    ]);

    expect($domain->createDate)->toBeNull()
        ->and($domain->expireDate)->toBeNull();
});

test('it handles empty dates', function (): void {
    $domain = Domain::fromArray([
        'domain' => 'example.com',
        'status' => 'ACTIVE',
        'createDate' => '',
        'expireDate' => '',
    ]);

    expect($domain->createDate)->toBeNull()
        ->and($domain->expireDate)->toBeNull();
});

test('it parses labels', function (): void {
    $domain = Domain::fromArray([
        'domain' => 'example.com',
        'status' => 'ACTIVE',
        'labels' => [
            ['id' => '1', 'title' => 'Production', 'color' => '#FF0000'],
            ['id' => '2', 'title' => 'Critical', 'color' => '#00FF00'],
        ],
    ]);

    expect($domain->labels)->toBeArray()
        ->and($domain->labels)->toHaveCount(2);

    /** @var array<DomainLabel> $labels */
    $labels = $domain->labels;
    expect($labels[0])->toBeInstanceOf(DomainLabel::class)
        ->and($labels[0]->title)->toBe('Production');
});

test('toArray serializes all fields correctly', function (): void {
    $domain = Domain::fromArray([
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

    $array = $domain->toArray();

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
    $domain = Domain::fromArray([
        'domain' => 'example.com',
        'status' => 'ACTIVE',
    ]);

    $array = $domain->toArray();

    expect($array)->toBe([
        'domain' => 'example.com',
        'status' => 'ACTIVE',
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

test('getTld returns explicit tld or extracts from domain', function (): void {
    $withTld = new Domain(
        domain: 'example.com',
        status: 'ACTIVE',
        tld: 'com',
    );
    $withoutTld = new Domain(
        domain: 'example.co.uk',
        status: 'ACTIVE',
    );

    expect($withTld->getTld())->toBe('com')
        ->and($withoutTld->getTld())->toBe('uk');
});
