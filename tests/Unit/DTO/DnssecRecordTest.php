<?php

declare(strict_types=1);

use Porkbun\DTO\DnssecRecord;

it('creates dnssec record from array', function (): void {
    $data = [
        'keyTag' => '12345',
        'algorithm' => '8',
        'digestType' => '2',
        'digest' => 'ABCDEF1234567890',
        'maxSigLife' => '86400',
        'flags' => '257',
        'protocol' => '3',
        'publicKey' => 'AwEAAd1KqJZW...',
    ];

    $dnssecRecord = DnssecRecord::fromArray($data);

    expect($dnssecRecord->keyTag)->toBe(12345)
        ->and($dnssecRecord->algorithm)->toBe(8)
        ->and($dnssecRecord->digestType)->toBe(2)
        ->and($dnssecRecord->digest)->toBe('ABCDEF1234567890')
        ->and($dnssecRecord->maxSigLife)->toBe(86400)
        ->and($dnssecRecord->flags)->toBe(257)
        ->and($dnssecRecord->protocol)->toBe(3)
        ->and($dnssecRecord->publicKey)->toBe('AwEAAd1KqJZW...');
});

it('handles alternative field names', function (): void {
    $data = [
        'keyTag' => '54321',
        'alg' => '13',  // Alternative to 'algorithm'
        'digestType' => '2',
        'digest' => 'FEDCBA0987654321',
        'keyDataPubKey' => 'AwEAAd1KqJZW...',  // Alternative to 'publicKey'
    ];

    $dnssecRecord = DnssecRecord::fromArray($data);

    expect($dnssecRecord->keyTag)->toBe(54321)
        ->and($dnssecRecord->algorithm)->toBe(13)
        ->and($dnssecRecord->publicKey)->toBe('AwEAAd1KqJZW...');
});

it('converts to array with proper field names', function (): void {
    $record = new DnssecRecord(
        keyTag: 12345,
        algorithm: 8,
        digestType: 2,
        digest: 'ABCDEF',
        maxSigLife: 86400,
        flags: 257,
        protocol: 3,
        publicKey: 'AwEAAd1KqJZW...',
    );

    $array = $record->toArray();

    expect($array)->toMatchArray([
        'keyTag' => '12345',
        'alg' => '8',
        'digestType' => '2',
        'digest' => 'ABCDEF',
        'maxSigLife' => '86400',
        'keyDataFlags' => '257',
        'keyDataProtocol' => '3',
        'keyDataAlgo' => '8',
        'keyDataPubKey' => 'AwEAAd1KqJZW...',
    ]);
});

it('identifies algorithm names correctly', function (): void {
    $algorithms = [
        1 => 'RSAMD5',
        8 => 'RSASHA256',
        13 => 'ECDSAP256SHA256',
        15 => 'ED25519',
        999 => 'Algorithm 999',
    ];

    foreach ($algorithms as $id => $expectedName) {
        $record = new DnssecRecord(
            keyTag: 1,
            algorithm: $id,
            digestType: 2,
            digest: 'ABC',
        );

        expect($record->algorithmName)->toBe($expectedName);
    }
});

it('identifies digest type names correctly', function (): void {
    $digestTypes = [
        1 => 'SHA-1',
        2 => 'SHA-256',
        4 => 'SHA-384',
        999 => 'Digest Type 999',
    ];

    foreach ($digestTypes as $id => $expectedName) {
        $record = new DnssecRecord(
            keyTag: 1,
            algorithm: 8,
            digestType: $id,
            digest: 'ABC',
        );

        expect($record->digestTypeName)->toBe($expectedName);
    }
});

it('detects KSK and ZSK keys correctly', function (): void {
    // KSK (Key Signing Key) - flags = 257
    $ksk = new DnssecRecord(
        keyTag: 1,
        algorithm: 8,
        digestType: 2,
        digest: 'ABC',
        flags: 257,
    );

    expect($ksk->isKsk)->toBeTrue()
        ->and($ksk->isZsk)->toBeFalse()
        ->and($ksk->isSecureEntryPoint)->toBeTrue();

    // ZSK (Zone Signing Key) - flags = 256
    $zsk = new DnssecRecord(
        keyTag: 2,
        algorithm: 8,
        digestType: 2,
        digest: 'DEF',
        flags: 256,
    );

    expect($zsk->isKsk)->toBeFalse()
        ->and($zsk->isZsk)->toBeTrue()
        ->and($zsk->isSecureEntryPoint)->toBeFalse();
});

it('identifies modern algorithms and digest types', function (): void {
    $modernRecord = new DnssecRecord(
        keyTag: 1,
        algorithm: 13,  // ECDSAP256SHA256
        digestType: 2,   // SHA-256
        digest: 'ABC',
    );

    expect($modernRecord->isModernAlgorithm)->toBeTrue()
        ->and($modernRecord->isModernDigestType)->toBeTrue();

    $legacyRecord = new DnssecRecord(
        keyTag: 2,
        algorithm: 5,   // RSASHA1 (legacy)
        digestType: 1,  // SHA-1 (legacy)
        digest: 'DEF',
    );

    expect($legacyRecord->isModernAlgorithm)->toBeFalse()
        ->and($legacyRecord->isModernDigestType)->toBeFalse();
});

it('handles optional fields correctly', function (): void {
    $minimal = new DnssecRecord(
        keyTag: 12345,
        algorithm: 8,
        digestType: 2,
        digest: 'ABCDEF',
    );

    expect($minimal->maxSigLife)->toBeNull()
        ->and($minimal->flags)->toBeNull()
        ->and($minimal->protocol)->toBeNull()
        ->and($minimal->publicKey)->toBeNull();

    $array = $minimal->toArray();
    expect($array)->not->toHaveKey('maxSigLife')
        ->and($array)->not->toHaveKey('keyDataFlags')
        ->and($array)->not->toHaveKey('keyDataProtocol')
        ->and($array)->not->toHaveKey('keyDataPubKey');
});
