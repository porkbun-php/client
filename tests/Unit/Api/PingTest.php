<?php

declare(strict_types=1);

use Porkbun\Api\Ping;
use Porkbun\DTO\PingData;

test('ping api returns ping data', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'yourIp' => '192.0.2.1'],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $ping = new Ping($httpClient);

    $pingData = $ping->check();

    expect($pingData)->toBeInstanceOf(PingData::class)
        ->and($pingData->yourIp)->toBe('192.0.2.1')
        ->and($pingData->hasIp())->toBeTrue();
});

test('ping api handles x-forwarded-for', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'yourIp' => '192.0.2.1', 'xForwardedFor' => '10.0.0.1'],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $ping = new Ping($httpClient);

    $pingData = $ping->check();

    expect($pingData->yourIp)->toBe('192.0.2.1')
        ->and($pingData->xForwardedFor)->toBe('10.0.0.1')
        ->and($pingData->hasForwardedIp())->toBeTrue()
        ->and($pingData->ip())->toBe('10.0.0.1');
});

test('ping api handles missing ip', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS'],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $ping = new Ping($httpClient);

    $pingData = $ping->check();

    expect($pingData->yourIp)->toBeNull()
        ->and($pingData->hasIp())->toBeFalse();
});
