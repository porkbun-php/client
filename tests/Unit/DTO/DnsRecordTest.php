<?php

declare(strict_types=1);

use Porkbun\DTO\DnsRecord;
use Porkbun\Enum\DnsRecordType;

test('it creates dns record from array', function (): void {
    $dnsRecord = DnsRecord::fromArray([
        'id' => '123',
        'name' => 'www',
        'type' => 'A',
        'content' => '192.0.2.1',
        'ttl' => '3600',
        'prio' => '0',
        'notes' => 'Web server',
    ]);

    expect($dnsRecord->id)->toBe(123)
        ->and($dnsRecord->name)->toBe('www')
        ->and($dnsRecord->type)->toBe(DnsRecordType::A)
        ->and($dnsRecord->content)->toBe('192.0.2.1')
        ->and($dnsRecord->ttl)->toBe(3600)
        ->and($dnsRecord->priority)->toBe(0)
        ->and($dnsRecord->notes)->toBe('Web server');
});

test('it handles missing notes', function (): void {
    $dnsRecord = DnsRecord::fromArray([
        'id' => '123',
        'name' => 'www',
        'type' => 'A',
        'content' => '192.0.2.1',
    ]);

    expect($dnsRecord->notes)->toBeNull();
});

test('toArray serializes all fields', function (): void {
    $record = new DnsRecord(
        id: 456,
        name: 'mail',
        type: DnsRecordType::MX,
        content: 'mail.example.com',
        ttl: 7200,
        priority: 10,
        notes: 'Mail server',
    );

    expect($record->toArray())->toBe([
        'id' => 456,
        'name' => 'mail',
        'type' => 'MX',
        'content' => 'mail.example.com',
        'ttl' => 7200,
        'prio' => 10,
        'notes' => 'Mail server',
    ]);
});

test('isRootRecord detects root records', function (): void {
    $root1 = new DnsRecord(1, '', DnsRecordType::A, '192.0.2.1', 600, 0);
    $root2 = new DnsRecord(2, '@', DnsRecordType::A, '192.0.2.1', 600, 0);
    $subdomain = new DnsRecord(3, 'www', DnsRecordType::A, '192.0.2.1', 600, 0);

    expect($root1->isRootRecord)->toBeTrue()
        ->and($root2->isRootRecord)->toBeTrue()
        ->and($subdomain->isRootRecord)->toBeFalse();
});

test('isType checks type with string', function (): void {
    $record = new DnsRecord(1, 'www', DnsRecordType::A, '192.0.2.1', 600, 0);

    expect($record->isType('A'))->toBeTrue()
        ->and($record->isType('a'))->toBeTrue()
        ->and($record->isType('MX'))->toBeFalse()
        ->and($record->isType('INVALID'))->toBeFalse();
});

test('isType checks type with enum', function (): void {
    $record = new DnsRecord(1, 'www', DnsRecordType::A, '192.0.2.1', 600, 0);

    expect($record->isType(DnsRecordType::A))->toBeTrue()
        ->and($record->isType(DnsRecordType::MX))->toBeFalse();
});

test('it creates ALIAS record from array', function (): void {
    $dnsRecord = DnsRecord::fromArray([
        'id' => '100',
        'name' => '',
        'type' => 'ALIAS',
        'content' => 'other.example.com',
        'ttl' => '600',
        'prio' => '0',
    ]);

    expect($dnsRecord->type)->toBe(DnsRecordType::ALIAS)
        ->and($dnsRecord->content)->toBe('other.example.com');
});

test('it creates SSHFP record from array', function (): void {
    $dnsRecord = DnsRecord::fromArray([
        'id' => '101',
        'name' => '',
        'type' => 'SSHFP',
        'content' => '1 2 abc123def456',
        'ttl' => '600',
        'prio' => '0',
    ]);

    expect($dnsRecord->type)->toBe(DnsRecordType::SSHFP)
        ->and($dnsRecord->content)->toBe('1 2 abc123def456');
});
