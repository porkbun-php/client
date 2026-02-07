<?php

declare(strict_types=1);

use Porkbun\DTO\PingData;

test('it creates ping data from array', function (): void {
    $pingData = PingData::fromArray([
        'yourIp' => '192.0.2.1',
        'xForwardedFor' => '10.0.0.1',
    ]);

    expect($pingData->yourIp)->toBe('192.0.2.1')
        ->and($pingData->xForwardedFor)->toBe('10.0.0.1');
});

test('it handles missing fields', function (): void {
    $pingData = PingData::fromArray([]);

    expect($pingData->yourIp)->toBeNull()
        ->and($pingData->xForwardedFor)->toBeNull();
});

test('ip returns forwarded ip when available', function (): void {
    $pingData = PingData::fromArray([
        'yourIp' => '192.0.2.1',
        'xForwardedFor' => '10.0.0.1',
    ]);
    $withoutForwarded = PingData::fromArray([
        'yourIp' => '192.0.2.1',
    ]);

    expect($pingData->ip())->toBe('10.0.0.1')
        ->and($withoutForwarded->ip())->toBe('192.0.2.1');
});

test('hasIp checks for non-empty yourIp', function (): void {
    $pingData = PingData::fromArray(['yourIp' => '192.0.2.1']);
    $emptyIp = PingData::fromArray(['yourIp' => '']);
    $nullIp = PingData::fromArray([]);

    expect($pingData->hasIp())->toBeTrue()
        ->and($emptyIp->hasIp())->toBeFalse()
        ->and($nullIp->hasIp())->toBeFalse();
});

test('hasForwardedIp checks for xForwardedFor', function (): void {
    $pingData = PingData::fromArray(['xForwardedFor' => '10.0.0.1']);
    $withoutForwarded = PingData::fromArray(['yourIp' => '192.0.2.1']);

    expect($pingData->hasForwardedIp())->toBeTrue()
        ->and($withoutForwarded->hasForwardedIp())->toBeFalse();
});

test('toArray serializes correctly', function (): void {
    $data = new PingData(
        yourIp: '192.0.2.1',
        xForwardedFor: '10.0.0.1',
    );

    expect($data->toArray())->toBe([
        'yourIp' => '192.0.2.1',
        'xForwardedFor' => '10.0.0.1',
    ]);
});

test('jsonSerialize returns toArray', function (): void {
    $data = new PingData(yourIp: '192.0.2.1', xForwardedFor: null);

    expect($data->jsonSerialize())->toBe($data->toArray());
});
