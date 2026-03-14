<?php

declare(strict_types=1);

use Porkbun\DTO\GlueRecord;

it('creates glue record from array with multiple IPs', function (): void {
    $data = [
        'host' => 'ns1',
        'ips' => ['192.0.2.1', '192.0.2.2', '2001:db8::1'],
    ];

    $glueRecord = GlueRecord::fromArray($data);

    expect($glueRecord->host)->toBe('ns1')
        ->and($glueRecord->ips)->toBe(['192.0.2.1', '192.0.2.2', '2001:db8::1'])
        ->and($glueRecord->hasIpv4)->toBeTrue()
        ->and($glueRecord->hasIpv6)->toBeTrue()
        ->and($glueRecord->ipCount)->toBe(3);
});

it('handles different IP field formats', function (): void {
    // Single IP field
    $data1 = [
        'host' => 'ns1',
        'ip' => '192.0.2.1',
    ];

    $glueRecord = GlueRecord::fromArray($data1);
    expect($glueRecord->ips)->toBe(['192.0.2.1']);

    // Numbered IP fields
    $data2 = [
        'subdomain' => 'ns2',
        'ip1' => '192.0.2.1',
        'ip2' => '192.0.2.2',
        'ip3' => '2001:db8::1',
    ];

    $record2 = GlueRecord::fromArray($data2);
    expect($record2->host)->toBe('ns2')
        ->and($record2->ips)->toBe(['192.0.2.1', '192.0.2.2', '2001:db8::1']);
});

it('filters IPv4 and IPv6 addresses correctly', function (): void {
    $record = new GlueRecord(
        host: 'ns1',
        ips: ['192.0.2.1', '198.51.100.1', '2001:db8::1', '2001:db8::2'],
    );

    expect($record->ipv4Addresses)->toBe(['192.0.2.1', '198.51.100.1'])
        ->and($record->ipv6Addresses)->toBe(['2001:db8::1', '2001:db8::2']);
});

it('detects IPv4-only, IPv6-only, and dual-stack records', function (): void {
    $ipv4Only = new GlueRecord('ns1', ['192.0.2.1', '198.51.100.1']);
    expect($ipv4Only->hasIpv4)->toBeTrue()
        ->and($ipv4Only->hasIpv6)->toBeFalse();

    $ipv6Only = new GlueRecord('ns2', ['2001:db8::1', '2001:db8::2']);
    expect($ipv6Only->hasIpv4)->toBeFalse()
        ->and($ipv6Only->hasIpv6)->toBeTrue();

    $dualStack = new GlueRecord('ns3', ['192.0.2.1', '2001:db8::1']);
    expect($dualStack->hasIpv4)->toBeTrue()
        ->and($dualStack->hasIpv6)->toBeTrue();
});

it('converts to array', function (): void {
    $record = new GlueRecord(
        host: 'ns1',
        ips: ['192.0.2.1', '192.0.2.2'],
    );

    expect($record->toArray())->toBe([
        'host' => 'ns1',
        'ips' => ['192.0.2.1', '192.0.2.2'],
    ]);
});

it('generates full hostname', function (): void {
    $record = new GlueRecord('ns1', ['192.0.2.1']);

    expect($record->fullHostname('example.com'))->toBe('ns1.example.com');
});

it('generates full hostname for empty host', function (): void {
    $record = new GlueRecord('', ['192.0.2.1']);

    expect($record->fullHostname('example.com'))->toBe('example.com');
});

it('removes duplicate IPs when creating from array', function (): void {
    $data = [
        'host' => 'ns1',
        'ips' => ['192.0.2.1', '192.0.2.1', '192.0.2.2'],
    ];

    $glueRecord = GlueRecord::fromArray($data);

    expect($glueRecord->ips)->toBe(['192.0.2.1', '192.0.2.2'])
        ->and($glueRecord->ipCount)->toBe(2);
});
