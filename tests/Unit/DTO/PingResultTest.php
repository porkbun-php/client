<?php

declare(strict_types=1);

use Porkbun\DTO\PingResult;

test('it creates ping data from array', function (): void {
    $pingResult = PingResult::fromArray([
        'yourIp' => '192.0.2.1',
        'xForwardedFor' => '10.0.0.1',
    ]);

    expect($pingResult->yourIp)->toBe('192.0.2.1')
        ->and($pingResult->forwardedIp)->toBe('10.0.0.1');
});

test('it handles missing fields', function (): void {
    $pingResult = PingResult::fromArray([]);

    expect($pingResult->yourIp)->toBeNull()
        ->and($pingResult->forwardedIp)->toBeNull();
});

test('ip returns forwarded ip when available', function (): void {
    $pingResult = PingResult::fromArray([
        'yourIp' => '192.0.2.1',
        'xForwardedFor' => '10.0.0.1',
    ]);
    $withoutForwarded = PingResult::fromArray([
        'yourIp' => '192.0.2.1',
    ]);

    expect($pingResult->resolvedIp)->toBe('10.0.0.1')
        ->and($withoutForwarded->resolvedIp)->toBe('192.0.2.1');
});

test('hasIp checks for non-empty yourIp', function (): void {
    $pingResult = PingResult::fromArray(['yourIp' => '192.0.2.1']);
    $emptyIp = PingResult::fromArray(['yourIp' => '']);
    $nullIp = PingResult::fromArray([]);

    expect($pingResult->hasIp)->toBeTrue()
        ->and($emptyIp->hasIp)->toBeFalse()
        ->and($nullIp->hasIp)->toBeFalse();
});

test('hasForwardedIp checks for non-empty forwardedIp', function (): void {
    $pingResult = PingResult::fromArray(['xForwardedFor' => '10.0.0.1']);
    $emptyForwarded = PingResult::fromArray(['xForwardedFor' => '']);
    $withoutForwarded = PingResult::fromArray(['yourIp' => '192.0.2.1']);

    expect($pingResult->hasForwardedIp)->toBeTrue()
        ->and($emptyForwarded->hasForwardedIp)->toBeFalse()
        ->and($withoutForwarded->hasForwardedIp)->toBeFalse();
});

test('toArray serializes correctly', function (): void {
    $data = new PingResult(
        yourIp: '192.0.2.1',
        forwardedIp: '10.0.0.1',
    );

    expect($data->toArray())->toBe([
        'yourIp' => '192.0.2.1',
        'forwardedIp' => '10.0.0.1',
    ]);
});

test('toArray omits null fields', function (): void {
    $onlyIp = new PingResult(yourIp: '192.0.2.1', forwardedIp: null);
    $bothNull = new PingResult(yourIp: null, forwardedIp: null);

    expect($onlyIp->toArray())->toBe(['yourIp' => '192.0.2.1'])
        ->and($onlyIp->toArray())->not->toHaveKey('forwardedIp')
        ->and($bothNull->toArray())->toBe([]);
});

test('round-trip through fromArray and toArray', function (): void {
    $original = new PingResult(yourIp: '192.0.2.1', forwardedIp: '10.0.0.1');
    $roundTripped = PingResult::fromArray($original->toArray());

    expect($roundTripped->yourIp)->toBe($original->yourIp)
        ->and($roundTripped->forwardedIp)->toBe($original->forwardedIp);
});

test('round-trip with null fields', function (): void {
    $original = new PingResult(yourIp: '192.0.2.1', forwardedIp: null);
    $roundTripped = PingResult::fromArray($original->toArray());

    expect($roundTripped->yourIp)->toBe('192.0.2.1')
        ->and($roundTripped->forwardedIp)->toBeNull();
});

test('fromArray accepts both wire format and canonical format', function (): void {
    $fromWire = PingResult::fromArray(['yourIp' => '1.2.3.4', 'xForwardedFor' => '5.6.7.8']);
    $fromCanonical = PingResult::fromArray(['yourIp' => '1.2.3.4', 'forwardedIp' => '5.6.7.8']);

    expect($fromWire->forwardedIp)->toBe('5.6.7.8')
        ->and($fromCanonical->forwardedIp)->toBe('5.6.7.8');
});

test('jsonSerialize returns toArray', function (): void {
    $data = new PingResult(yourIp: '192.0.2.1', forwardedIp: null);

    expect($data->jsonSerialize())->toBe($data->toArray());
});
