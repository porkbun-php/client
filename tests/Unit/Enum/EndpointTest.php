<?php

declare(strict_types=1);

use Porkbun\Enum\Endpoint;

test('endpoint has correct urls', function (): void {
    expect(Endpoint::DEFAULT->value)->toBe('https://api.porkbun.com/api/json/v3')
        ->and(Endpoint::IPV4->value)->toBe('https://api-ipv4.porkbun.com/api/json/v3');
});

test('url returns endpoint value', function (): void {
    expect(Endpoint::DEFAULT->url())->toBe('https://api.porkbun.com/api/json/v3')
        ->and(Endpoint::IPV4->url())->toBe('https://api-ipv4.porkbun.com/api/json/v3');
});

test('type returns type name', function (): void {
    expect(Endpoint::DEFAULT->type())->toBe('default')
        ->and(Endpoint::IPV4->type())->toBe('ipv4');
});

test('fromUrl finds endpoint by url', function (): void {
    expect(Endpoint::fromUrl('https://api.porkbun.com/api/json/v3'))->toBe(Endpoint::DEFAULT)
        ->and(Endpoint::fromUrl('https://api-ipv4.porkbun.com/api/json/v3'))->toBe(Endpoint::IPV4);
});

test('fromUrl handles trailing slash', function (): void {
    expect(Endpoint::fromUrl('https://api.porkbun.com/api/json/v3/'))->toBe(Endpoint::DEFAULT);
});

test('fromUrl returns null for unknown url', function (): void {
    expect(Endpoint::fromUrl('https://unknown.example.com'))->toBeNull();
});

test('fromType finds endpoint by type name', function (): void {
    expect(Endpoint::fromType('default'))->toBe(Endpoint::DEFAULT)
        ->and(Endpoint::fromType('ipv4'))->toBe(Endpoint::IPV4)
        ->and(Endpoint::fromType('DEFAULT'))->toBe(Endpoint::DEFAULT)
        ->and(Endpoint::fromType('IPV4'))->toBe(Endpoint::IPV4);
});

test('fromType returns null for unknown type', function (): void {
    expect(Endpoint::fromType('unknown'))->toBeNull();
});

test('all returns all endpoints', function (): void {
    $all = Endpoint::all();

    expect($all)->toBe([
        'default' => 'https://api.porkbun.com/api/json/v3',
        'ipv4' => 'https://api-ipv4.porkbun.com/api/json/v3',
    ]);
});

test('getDefault returns default endpoint', function (): void {
    expect(Endpoint::getDefault())->toBe(Endpoint::DEFAULT);
});

test('isKnownUrl checks if url is known', function (): void {
    expect(Endpoint::isKnownUrl('https://api.porkbun.com/api/json/v3'))->toBeTrue()
        ->and(Endpoint::isKnownUrl('https://api-ipv4.porkbun.com/api/json/v3'))->toBeTrue()
        ->and(Endpoint::isKnownUrl('https://unknown.example.com'))->toBeFalse();
});

test('typeFromUrl returns type or custom', function (): void {
    expect(Endpoint::typeFromUrl('https://api.porkbun.com/api/json/v3'))->toBe('default')
        ->and(Endpoint::typeFromUrl('https://api-ipv4.porkbun.com/api/json/v3'))->toBe('ipv4')
        ->and(Endpoint::typeFromUrl('https://custom.example.com'))->toBe('custom');
});
