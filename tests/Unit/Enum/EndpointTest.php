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
