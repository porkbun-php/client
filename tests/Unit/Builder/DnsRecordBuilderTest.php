<?php

declare(strict_types=1);

use Porkbun\Builder\DnsRecordBuilder;
use Porkbun\Exception\InvalidArgumentException;

test('dns record builder can build basic A record', function (): void {
    $builder = new DnsRecordBuilder();

    $data = $builder
        ->name('www')
        ->type('A')
        ->content('192.0.2.1')
        ->ttl(3600)
        ->getData();

    expect($data)->toBe([
        'name' => 'www',
        'type' => 'A',
        'content' => '192.0.2.1',
        'ttl' => '3600',
        'prio' => '0',
    ]);
});

test('dns record builder can build MX record with priority', function (): void {
    $builder = new DnsRecordBuilder();

    $data = $builder
        ->name('mail')
        ->type('MX')
        ->content('mail.example.com')
        ->priority(10)
        ->notes('Mail server')
        ->getData();

    expect($data)->toBe([
        'name' => 'mail',
        'type' => 'MX',
        'content' => 'mail.example.com',
        'ttl' => '600',
        'prio' => '10',
        'notes' => 'Mail server',
    ]);
});

test('dns record builder omits empty notes', function (): void {
    $builder = new DnsRecordBuilder();

    $data = $builder
        ->name('test')
        ->type('A')
        ->content('203.0.113.1')
        ->getData();

    expect($data)->not()->toHaveKey('notes');
});

test('dns record builder validates required fields', function (): void {
    $builder = new DnsRecordBuilder();

    expect(fn (): array => $builder->getData())
        ->toThrow(InvalidArgumentException::class, 'Record type is required');
});

test('dns record builder validates content is required', function (): void {
    $builder = new DnsRecordBuilder();

    expect(fn (): array => $builder->type('A')->getData())
        ->toThrow(InvalidArgumentException::class, 'Content is required');
});

test('dns record builder validates record type', function (): void {
    $builder = new DnsRecordBuilder();

    expect(fn (): DnsRecordBuilder => $builder->type('INVALID'))
        ->toThrow(InvalidArgumentException::class, 'Invalid record type: INVALID');
});

test('dns record builder validates empty content', function (): void {
    $builder = new DnsRecordBuilder();

    expect(fn (): DnsRecordBuilder => $builder->content('   '))
        ->toThrow(InvalidArgumentException::class, 'Content cannot be empty');
});

test('dns record builder validates ttl', function (): void {
    $builder = new DnsRecordBuilder();

    expect(fn (): DnsRecordBuilder => $builder->ttl(0))
        ->toThrow(InvalidArgumentException::class, 'TTL must be greater than 0');
});

test('dns record builder validates priority', function (): void {
    $builder = new DnsRecordBuilder();

    expect(fn (): DnsRecordBuilder => $builder->priority(-1))
        ->toThrow(InvalidArgumentException::class, 'Priority cannot be negative');
});

test('dns record builder has convenience methods', function (): void {
    $builder = new DnsRecordBuilder();

    $data = $builder->name('www')->a('192.0.2.1')->getData();
    expect($data['type'])->toBe('A');
    expect($data['content'])->toBe('192.0.2.1');

    $builder->reset();
    $data = $builder->name('www')->aaaa('2001:db8::1')->getData();
    expect($data['type'])->toBe('AAAA');

    $builder->reset();
    $data = $builder->name('www')->cname('example.com')->getData();
    expect($data['type'])->toBe('CNAME');

    $builder->reset();
    $data = $builder->name('mail')->mx('mail.example.com', 20)->getData();
    expect($data['type'])->toBe('MX');
    expect($data['prio'])->toBe('20');

    $builder->reset();
    $data = $builder->name('_dmarc')->txt('v=DMARC1;')->getData();
    expect($data['type'])->toBe('TXT');

    $builder->reset();
    $data = $builder->name('sub')->ns('ns1.example.com')->getData();
    expect($data['type'])->toBe('NS');
});

test('dns record builder can be reset', function (): void {
    $builder = new DnsRecordBuilder();

    $builder->name('test')->type('A')->content('203.0.113.1')->notes('test');
    $builder->reset();

    expect(fn (): array => $builder->getData())
        ->toThrow(InvalidArgumentException::class, 'Record type is required');
});

test('dns record builder is fluent', function (): void {
    $builder = new DnsRecordBuilder();

    $dnsRecordBuilder = $builder
        ->name('www')
        ->type('A')
        ->content('203.0.113.1')
        ->ttl(7200)
        ->priority(5)
        ->notes('Web server');

    expect($dnsRecordBuilder)->toBe($builder);
});

test('dns record builder validates content regardless of call order', function (): void {
    $builder = new DnsRecordBuilder();

    // Set invalid content first, then set type - should still validate
    expect(fn (): DnsRecordBuilder => $builder->content('not-an-ip')->type('A'))
        ->toThrow(InvalidArgumentException::class, 'Invalid content for A record');
});
