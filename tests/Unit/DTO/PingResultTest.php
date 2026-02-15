<?php

declare(strict_types=1);

use Porkbun\DTO\PingResult;

test('it creates ping data from array', function (): void {
    $pingResult = PingResult::fromArray([
        'yourIp' => '192.0.2.1',
        'xForwardedFor' => '10.0.0.1',
    ]);

    expect($pingResult->yourIp)->toBe('192.0.2.1')
        ->and($pingResult->xForwardedFor)->toBe('10.0.0.1');
});

test('it handles missing fields', function (): void {
    $pingResult = PingResult::fromArray([]);

    expect($pingResult->yourIp)->toBeNull()
        ->and($pingResult->xForwardedFor)->toBeNull();
});

test('ip returns forwarded ip when available', function (): void {
    $pingResult = PingResult::fromArray([
        'yourIp' => '192.0.2.1',
        'xForwardedFor' => '10.0.0.1',
    ]);
    $withoutForwarded = PingResult::fromArray([
        'yourIp' => '192.0.2.1',
    ]);

    expect($pingResult->ip())->toBe('10.0.0.1')
        ->and($withoutForwarded->ip())->toBe('192.0.2.1');
});

test('hasIp checks for non-empty yourIp', function (): void {
    $pingResult = PingResult::fromArray(['yourIp' => '192.0.2.1']);
    $emptyIp = PingResult::fromArray(['yourIp' => '']);
    $nullIp = PingResult::fromArray([]);

    expect($pingResult->hasIp())->toBeTrue()
        ->and($emptyIp->hasIp())->toBeFalse()
        ->and($nullIp->hasIp())->toBeFalse();
});

test('hasForwardedIp checks for non-empty xForwardedFor', function (): void {
    $pingResult = PingResult::fromArray(['xForwardedFor' => '10.0.0.1']);
    $emptyForwarded = PingResult::fromArray(['xForwardedFor' => '']);
    $withoutForwarded = PingResult::fromArray(['yourIp' => '192.0.2.1']);

    expect($pingResult->hasForwardedIp())->toBeTrue()
        ->and($emptyForwarded->hasForwardedIp())->toBeFalse()
        ->and($withoutForwarded->hasForwardedIp())->toBeFalse();
});

test('toArray serializes correctly', function (): void {
    $data = new PingResult(
        yourIp: '192.0.2.1',
        xForwardedFor: '10.0.0.1',
    );

    expect($data->toArray())->toBe([
        'yourIp' => '192.0.2.1',
        'xForwardedFor' => '10.0.0.1',
    ]);
});

test('jsonSerialize returns toArray', function (): void {
    $data = new PingResult(yourIp: '192.0.2.1', xForwardedFor: null);

    expect($data->jsonSerialize())->toBe($data->toArray());
});
