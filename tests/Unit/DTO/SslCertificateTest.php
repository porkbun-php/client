<?php

declare(strict_types=1);

use Porkbun\DTO\SslCertificate;

test('it creates ssl certificate from array', function (): void {
    $sslCertificate = SslCertificate::fromArray([
        'certificatechain' => '-----BEGIN CERTIFICATE-----\nMIIC...',
        'privatekey' => '-----BEGIN PRIVATE KEY-----\nMIIE...',
        'publickey' => '-----BEGIN PUBLIC KEY-----\nMIIB...',
        'intermediatecertificate' => '-----BEGIN CERTIFICATE-----\nMIID...',
    ]);

    expect($sslCertificate->certificateChain)->toBe('-----BEGIN CERTIFICATE-----\nMIIC...')
        ->and($sslCertificate->privateKey)->toBe('-----BEGIN PRIVATE KEY-----\nMIIE...')
        ->and($sslCertificate->publicKey)->toBe('-----BEGIN PUBLIC KEY-----\nMIIB...')
        ->and($sslCertificate->intermediateCertificate)->toBe('-----BEGIN CERTIFICATE-----\nMIID...');
});

test('it handles missing intermediate certificate', function (): void {
    $sslCertificate = SslCertificate::fromArray([
        'certificatechain' => '-----BEGIN CERTIFICATE-----\nMIIC...',
        'privatekey' => '-----BEGIN PRIVATE KEY-----\nMIIE...',
        'publickey' => '-----BEGIN PUBLIC KEY-----\nMIIB...',
    ]);

    expect($sslCertificate->intermediateCertificate)->toBeNull();
});

test('getFullChain combines certificates', function (): void {
    $withIntermediate = new SslCertificate(
        certificateChain: 'CERT',
        privateKey: 'KEY',
        publicKey: 'PUB',
        intermediateCertificate: 'INTERMEDIATE',
    );
    $withoutIntermediate = new SslCertificate(
        certificateChain: 'CERT',
        privateKey: 'KEY',
        publicKey: 'PUB',
    );
    $emptyIntermediate = new SslCertificate(
        certificateChain: 'CERT',
        privateKey: 'KEY',
        publicKey: 'PUB',
        intermediateCertificate: '',
    );

    expect($withIntermediate->getFullChain())->toBe("CERT\nINTERMEDIATE")
        ->and($withoutIntermediate->getFullChain())->toBe('CERT')
        ->and($emptyIntermediate->getFullChain())->toBe('CERT');
});

test('hasCertificate checks for non-empty chain', function (): void {
    $withCert = new SslCertificate('CERT', 'KEY', 'PUB');
    $emptyCert = new SslCertificate('', 'KEY', 'PUB');

    expect($withCert->hasCertificate())->toBeTrue()
        ->and($emptyCert->hasCertificate())->toBeFalse();
});

test('hasPrivateKey checks for non-empty key', function (): void {
    $withKey = new SslCertificate('CERT', 'KEY', 'PUB');
    $emptyKey = new SslCertificate('CERT', '', 'PUB');

    expect($withKey->hasPrivateKey())->toBeTrue()
        ->and($emptyKey->hasPrivateKey())->toBeFalse();
});

test('hasIntermediateCertificate checks for non-empty intermediate', function (): void {
    $withIntermediate = new SslCertificate('CERT', 'KEY', 'PUB', 'INTERMEDIATE');
    $emptyIntermediate = new SslCertificate('CERT', 'KEY', 'PUB', '');
    $nullIntermediate = new SslCertificate('CERT', 'KEY', 'PUB');

    expect($withIntermediate->hasIntermediateCertificate())->toBeTrue()
        ->and($emptyIntermediate->hasIntermediateCertificate())->toBeFalse()
        ->and($nullIntermediate->hasIntermediateCertificate())->toBeFalse();
});

test('toArray serializes correctly', function (): void {
    $cert = new SslCertificate('CERT', 'KEY', 'PUB', 'INTERMEDIATE');

    expect($cert->toArray())->toBe([
        'certificatechain' => 'CERT',
        'privatekey' => 'KEY',
        'publickey' => 'PUB',
        'intermediatecertificate' => 'INTERMEDIATE',
    ]);
});

test('toArray omits null intermediate', function (): void {
    $cert = new SslCertificate('CERT', 'KEY', 'PUB');

    expect($cert->toArray())->toBe([
        'certificatechain' => 'CERT',
        'privatekey' => 'KEY',
        'publickey' => 'PUB',
    ]);
});

test('jsonSerialize returns toArray', function (): void {
    $cert = new SslCertificate('CERT', 'KEY', 'PUB');

    expect($cert->jsonSerialize())->toBe($cert->toArray());
});
