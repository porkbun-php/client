<?php

declare(strict_types=1);

use Porkbun\Api\Ssl;
use Porkbun\DTO\SslCertificate;
use Porkbun\Exception\ApiException;

test('ssl api can get certificate', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'certificatechain' => '-----BEGIN CERTIFICATE-----\nMIID...\n-----END CERTIFICATE-----',
            'privatekey' => '-----BEGIN PRIVATE KEY-----\nMIIE...\n-----END PRIVATE KEY-----',
            'publickey' => '-----BEGIN PUBLIC KEY-----\nMIIB...\n-----END PUBLIC KEY-----',
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $ssl = new Ssl(createMockContext($httpClient), 'example.com');

    $sslCertificate = $ssl->get();

    expect($sslCertificate)->toBeInstanceOf(SslCertificate::class)
        ->and($sslCertificate->hasCertificate)->toBeTrue()
        ->and($sslCertificate->hasPrivateKey)->toBeTrue()
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
    $ssl = new Ssl(createMockContext($httpClient), 'example.com');

    $sslCertificate = $ssl->get();

    expect($sslCertificate->hasIntermediateCertificate)->toBeTrue()
        ->and($sslCertificate->fullChain)->toContain('INTER');
});

test('ssl api throws ApiException when certificate not ready', function (): void {
    $mockClient = createMockHttpClient([
        [
            'body' => [
                'status' => 'ERROR',
                'message' => 'The SSL certificate is not ready for this domain.',
            ],
            'httpStatus' => 400,
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $ssl = new Ssl(createMockContext($httpClient), 'example.com');

    expect(fn (): SslCertificate => $ssl->get())->toThrow(
        ApiException::class,
        'The SSL certificate is not ready for this domain.'
    );
});
