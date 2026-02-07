<?php

declare(strict_types=1);

use Porkbun\Api\Ssl;
use Porkbun\DTO\SslCertificate;

test('ssl api can retrieve certificate', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'certificatechain' => '-----BEGIN CERTIFICATE-----\nMIID...\n-----END CERTIFICATE-----',
            'privatekey' => '-----BEGIN PRIVATE KEY-----\nMIIE...\n-----END PRIVATE KEY-----',
            'publickey' => '-----BEGIN PUBLIC KEY-----\nMIIB...\n-----END PUBLIC KEY-----',
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $ssl = new Ssl($httpClient, 'example.com');

    $sslCertificate = $ssl->retrieve();

    expect($sslCertificate)->toBeInstanceOf(SslCertificate::class)
        ->and($sslCertificate->hasCertificate())->toBeTrue()
        ->and($sslCertificate->hasPrivateKey())->toBeTrue()
        ->and($sslCertificate->certificateChain)->toContain('BEGIN CERTIFICATE');
});

test('ssl api handles intermediate certificate', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'certificatechain' => '-----BEGIN CERTIFICATE-----\nMIID...\n-----END CERTIFICATE-----',
            'privatekey' => '-----BEGIN PRIVATE KEY-----\nMIIE...\n-----END PRIVATE KEY-----',
            'publickey' => '-----BEGIN PUBLIC KEY-----\nMIIB...\n-----END PUBLIC KEY-----',
            'intermediatecertificate' => '-----BEGIN CERTIFICATE-----\nINTER...\n-----END CERTIFICATE-----',
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $ssl = new Ssl($httpClient, 'example.com');

    $sslCertificate = $ssl->retrieve();

    expect($sslCertificate->hasIntermediateCertificate())->toBeTrue()
        ->and($sslCertificate->getFullChain())->toContain('INTER');
});

test('ssl api returns correct domain', function (): void {
    $mockClient = createMockHttpClient([]);

    $httpClient = createHttpClient($mockClient);
    $ssl = new Ssl($httpClient, 'example.com');

    expect($ssl->getDomain())->toBe('example.com');
});
