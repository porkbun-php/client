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
        ->data();

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
        ->data();

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
        ->data();

    expect($data)->not()->toHaveKey('notes');
});

test('dns record builder validates required fields', function (): void {
    $builder = new DnsRecordBuilder();

    expect(fn (): array => $builder->data())
        ->toThrow(InvalidArgumentException::class, 'Record type is required');
});

test('dns record builder validates content is required', function (): void {
    $builder = new DnsRecordBuilder();

    expect(fn (): array => $builder->type('A')->data())
        ->toThrow(InvalidArgumentException::class, 'Content is required');
});

test('dns record builder validates record type', function (): void {
    $builder = new DnsRecordBuilder();

    expect(fn (): DnsRecordBuilder => $builder->type('INVALID'))
        ->toThrow(InvalidArgumentException::class, 'Invalid DNS record type: INVALID');
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
    $data = new DnsRecordBuilder()->name('www')->a('192.0.2.1')->data();
    expect($data['type'])->toBe('A');
    expect($data['content'])->toBe('192.0.2.1');

    $data = new DnsRecordBuilder()->name('www')->aaaa('2001:db8::1')->data();
    expect($data['type'])->toBe('AAAA');

    $data = new DnsRecordBuilder()->name('www')->cname('example.com')->data();
    expect($data['type'])->toBe('CNAME');

    $data = new DnsRecordBuilder()->name('mail')->mx('mail.example.com', 20)->data();
    expect($data['type'])->toBe('MX');
    expect($data['prio'])->toBe('20');

    $data = new DnsRecordBuilder()->name('_dmarc')->txt('v=DMARC1;')->data();
    expect($data['type'])->toBe('TXT');

    $data = new DnsRecordBuilder()->name('sub')->ns('ns1.example.com')->data();
    expect($data['type'])->toBe('NS');

    $data = new DnsRecordBuilder()->name('')->alias('other.example.com')->data();
    expect($data['type'])->toBe('ALIAS');
    expect($data['content'])->toBe('other.example.com');

    $data = new DnsRecordBuilder()->name('')->sshfp('1 2 abc123def456')->data();
    expect($data['type'])->toBe('SSHFP');
    expect($data['content'])->toBe('1 2 abc123def456');
});

test('dns record builder is immutable', function (): void {
    $builder = new DnsRecordBuilder();

    $dnsRecordBuilder = $builder
        ->name('www')
        ->type('A')
        ->content('203.0.113.1')
        ->ttl(7200)
        ->priority(5)
        ->notes('Web server');

    expect($dnsRecordBuilder)->toBeInstanceOf(DnsRecordBuilder::class);
    expect($dnsRecordBuilder)->not->toBe($builder);

    // Original builder is unchanged (still has no type/content)
    expect(fn (): array => $builder->data())
        ->toThrow(InvalidArgumentException::class, 'Record type is required');
});

test('dns record builder supports template pattern', function (): void {
    $dnsRecordBuilder = new DnsRecordBuilder()->ttl(3600)->notes('Production');

    $www = $dnsRecordBuilder->name('www')->a('192.0.2.1');
    $api = $dnsRecordBuilder->name('api')->a('192.0.2.2');
    $cdn = $dnsRecordBuilder->name('cdn')->cname('cdn.provider.com');

    // Base is unchanged (still no type/content)
    expect(fn (): array => $dnsRecordBuilder->data())
        ->toThrow(InvalidArgumentException::class, 'Record type is required');

    // Each fork is independent
    $wwwData = $www->data();
    $apiData = $api->data();
    $cdnData = $cdn->data();

    expect($wwwData['name'])->toBe('www');
    expect($wwwData['content'])->toBe('192.0.2.1');
    expect($wwwData['ttl'])->toBe('3600');
    expect($wwwData['notes'])->toBe('Production');

    expect($apiData['name'])->toBe('api');
    expect($apiData['content'])->toBe('192.0.2.2');

    expect($cdnData['name'])->toBe('cdn');
    expect($cdnData['type'])->toBe('CNAME');
});

test('dns record builder forks are independent', function (): void {
    $dnsRecordBuilder = new DnsRecordBuilder()->name('base')->type('A');

    $fork1 = $dnsRecordBuilder->content('10.0.0.1');
    $fork2 = $dnsRecordBuilder->content('10.0.0.2');

    expect($fork1->data()['content'])->toBe('10.0.0.1');
    expect($fork2->data()['content'])->toBe('10.0.0.2');
});

test('dns record builder validates content regardless of call order', function (): void {
    $builder = new DnsRecordBuilder();

    // Set invalid content first, then set type - should still validate
    expect(fn (): DnsRecordBuilder => $builder->content('not-an-ip')->type('A'))
        ->toThrow(InvalidArgumentException::class, 'Invalid content for A record');
});
