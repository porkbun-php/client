<?php

declare(strict_types=1);

use Porkbun\Api\Domains;
use Porkbun\DTO\SslCertificate;
use Porkbun\Exception\ApiException;
use Porkbun\Resource\Domain;

test('ssl can get certificate', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'certificatechain' => '-----BEGIN CERTIFICATE-----\nMIID...\n-----END CERTIFICATE-----',
            'privatekey' => '-----BEGIN PRIVATE KEY-----\nMIIE...\n-----END PRIVATE KEY-----',
            'publickey' => '-----BEGIN PUBLIC KEY-----\nMIIB...\n-----END PUBLIC KEY-----',
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $context = createMockContext($httpClient);
    $domain = new Domain('example.com', $context, new Domains($context));

    $sslCertificate = $domain->ssl();

    expect($sslCertificate)->toBeInstanceOf(SslCertificate::class)
        ->and($sslCertificate->hasCertificate)->toBeTrue()
        ->and($sslCertificate->hasPrivateKey)->toBeTrue()
        ->and($sslCertificate->certificateChain)->toContain('BEGIN CERTIFICATE');
});

test('ssl handles intermediate certificate', function (): void {
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
    $context = createMockContext($httpClient);
    $domain = new Domain('example.com', $context, new Domains($context));

    $sslCertificate = $domain->ssl();

    expect($sslCertificate->hasIntermediateCertificate)->toBeTrue()
        ->and($sslCertificate->fullChain)->toContain('INTER');
});

test('ssl throws ApiException when certificate not ready', function (): void {
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
    $context = createMockContext($httpClient);
    $domain = new Domain('example.com', $context, new Domains($context));

    expect(fn (): SslCertificate => $domain->ssl())->toThrow(
        ApiException::class,
        'The SSL certificate is not ready for this domain.'
    );
});
