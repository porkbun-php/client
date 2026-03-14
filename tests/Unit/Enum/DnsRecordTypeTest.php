<?php

declare(strict_types=1);

use Porkbun\Enum\DnsRecordType;

test('requiresPriority returns true for MX and SRV', function (): void {
    expect(DnsRecordType::MX->requiresPriority())->toBeTrue()
        ->and(DnsRecordType::SRV->requiresPriority())->toBeTrue();
});

test('requiresPriority returns false for other types', function (): void {
    expect(DnsRecordType::A->requiresPriority())->toBeFalse()
        ->and(DnsRecordType::AAAA->requiresPriority())->toBeFalse()
        ->and(DnsRecordType::CNAME->requiresPriority())->toBeFalse()
        ->and(DnsRecordType::TXT->requiresPriority())->toBeFalse()
        ->and(DnsRecordType::NS->requiresPriority())->toBeFalse()
        ->and(DnsRecordType::TLSA->requiresPriority())->toBeFalse()
        ->and(DnsRecordType::CAA->requiresPriority())->toBeFalse()
        ->and(DnsRecordType::HTTPS->requiresPriority())->toBeFalse()
        ->and(DnsRecordType::SVCB->requiresPriority())->toBeFalse()
        ->and(DnsRecordType::ALIAS->requiresPriority())->toBeFalse()
        ->and(DnsRecordType::SSHFP->requiresPriority())->toBeFalse();
});

test('description returns description for all types', function (): void {
    expect(DnsRecordType::A->description())->toBe('IPv4 address record')
        ->and(DnsRecordType::AAAA->description())->toBe('IPv6 address record')
        ->and(DnsRecordType::CNAME->description())->toBe('Canonical name record')
        ->and(DnsRecordType::MX->description())->toBe('Mail exchange record')
        ->and(DnsRecordType::TXT->description())->toBe('Text record')
        ->and(DnsRecordType::NS->description())->toBe('Name server record')
        ->and(DnsRecordType::SRV->description())->toBe('Service locator record')
        ->and(DnsRecordType::TLSA->description())->toBe('DANE DNS-based Authentication of Named Entities record')
        ->and(DnsRecordType::CAA->description())->toBe('Certification Authority Authorization record')
        ->and(DnsRecordType::HTTPS->description())->toBe('HTTPS service binding record')
        ->and(DnsRecordType::SVCB->description())->toBe('Service binding record')
        ->and(DnsRecordType::ALIAS->description())->toBe('CNAME flattening record')
        ->and(DnsRecordType::SSHFP->description())->toBe('SSH fingerprint record');
});

test('validateContent validates A records', function (): void {
    expect(DnsRecordType::A->validateContent('192.0.2.1'))->toBeTrue()
        ->and(DnsRecordType::A->validateContent('10.0.0.1'))->toBeTrue()
        ->and(DnsRecordType::A->validateContent('invalid'))->toBeFalse()
        ->and(DnsRecordType::A->validateContent('2001:db8::1'))->toBeFalse();
});

test('validateContent validates AAAA records', function (): void {
    expect(DnsRecordType::AAAA->validateContent('2001:db8::1'))->toBeTrue()
        ->and(DnsRecordType::AAAA->validateContent('::1'))->toBeTrue()
        ->and(DnsRecordType::AAAA->validateContent('192.0.2.1'))->toBeFalse()
        ->and(DnsRecordType::AAAA->validateContent('invalid'))->toBeFalse();
});

test('validateContent validates CNAME records', function (): void {
    expect(DnsRecordType::CNAME->validateContent('example.com'))->toBeTrue()
        ->and(DnsRecordType::CNAME->validateContent('sub.example.com'))->toBeTrue()
        ->and(DnsRecordType::CNAME->validateContent('example.com.'))->toBeTrue()
        ->and(DnsRecordType::CNAME->validateContent('-invalid.com'))->toBeFalse();
});

test('validateContent validates NS records', function (): void {
    expect(DnsRecordType::NS->validateContent('ns1.example.com'))->toBeTrue()
        ->and(DnsRecordType::NS->validateContent('ns.porkbun.com'))->toBeTrue();
});

test('validateContent validates MX records', function (): void {
    expect(DnsRecordType::MX->validateContent('mail.example.com'))->toBeTrue()
        ->and(DnsRecordType::MX->validateContent('mx1.google.com'))->toBeTrue();
});

test('validateContent allows any TXT content', function (): void {
    expect(DnsRecordType::TXT->validateContent('any text value'))->toBeTrue()
        ->and(DnsRecordType::TXT->validateContent('v=spf1 include:_spf.google.com ~all'))->toBeTrue()
        ->and(DnsRecordType::TXT->validateContent(''))->toBeTrue();
});

test('validateContent validates SRV records', function (): void {
    expect(DnsRecordType::SRV->validateContent('10 5 5060 sipserver.example.com'))->toBeTrue()
        ->and(DnsRecordType::SRV->validateContent('0 0 443 example.com'))->toBeTrue()
        ->and(DnsRecordType::SRV->validateContent('invalid'))->toBeFalse();
});

test('validateContent validates TLSA records', function (): void {
    expect(DnsRecordType::TLSA->validateContent('3 1 1 abc123def456'))->toBeTrue()
        ->and(DnsRecordType::TLSA->validateContent('0 0 1 AABBCCDD'))->toBeTrue()
        ->and(DnsRecordType::TLSA->validateContent('invalid'))->toBeFalse();
});

test('validateContent validates CAA records', function (): void {
    expect(DnsRecordType::CAA->validateContent('0 issue letsencrypt.org'))->toBeTrue()
        ->and(DnsRecordType::CAA->validateContent('0 issuewild ;'))->toBeTrue()
        ->and(DnsRecordType::CAA->validateContent('invalid'))->toBeFalse();
});

test('validateContent allows HTTPS and SVCB content', function (): void {
    expect(DnsRecordType::HTTPS->validateContent('1 . alpn=h2'))->toBeTrue()
        ->and(DnsRecordType::SVCB->validateContent('1 example.com port=443'))->toBeTrue();
});

test('validateContent validates ALIAS records', function (): void {
    expect(DnsRecordType::ALIAS->validateContent('example.com'))->toBeTrue()
        ->and(DnsRecordType::ALIAS->validateContent('sub.example.com'))->toBeTrue()
        ->and(DnsRecordType::ALIAS->validateContent('-invalid.com'))->toBeFalse();
});

test('validateContent validates SSHFP records', function (): void {
    expect(DnsRecordType::SSHFP->validateContent('1 2 abc123def456'))->toBeTrue()
        ->and(DnsRecordType::SSHFP->validateContent('3 1 AABBCCDD'))->toBeTrue()
        ->and(DnsRecordType::SSHFP->validateContent('invalid'))->toBeFalse()
        ->and(DnsRecordType::SSHFP->validateContent('1 2 notahex!'))->toBeFalse();
});
