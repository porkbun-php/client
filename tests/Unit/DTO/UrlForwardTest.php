<?php

declare(strict_types=1);

use Porkbun\DTO\UrlForward;
use Porkbun\Enum\UrlForwardType;
use Porkbun\Exception\InvalidArgumentException;

it('creates url forward from array', function (): void {
    $data = [
        'id' => '12345',
        'subdomain' => 'www',
        'location' => 'https://example.com',
        'type' => 'permanent',
        'includePath' => 'yes',
        'wildcard' => 'no',
    ];

    $urlForward = UrlForward::fromArray($data);

    expect($urlForward->id)->toBe(12345)
        ->and($urlForward->subdomain)->toBe('www')
        ->and($urlForward->location)->toBe('https://example.com')
        ->and($urlForward->type)->toBe(UrlForwardType::PERMANENT)
        ->and($urlForward->includePath)->toBeTrue()
        ->and($urlForward->wildcard)->toBeFalse();
});

it('creates root domain forward', function (): void {
    $data = [
        'id' => '12345',
        'location' => 'https://example.com',
        'type' => 'temporary',
        'includePath' => 'no',
        'wildcard' => 'yes',
    ];

    $urlForward = UrlForward::fromArray($data);

    expect($urlForward->subdomain)->toBe('')
        ->and($urlForward->isRootDomain)->toBeTrue()
        ->and($urlForward->isPermanent)->toBeFalse()
        ->and($urlForward->isTemporary)->toBeTrue();
});

it('converts to array', function (): void {
    $forward = new UrlForward(
        id: 123,
        subdomain: 'blog',
        location: 'https://blog.example.com',
        type: UrlForwardType::PERMANENT,
        includePath: true,
        wildcard: false,
    );

    $array = $forward->toArray();

    expect($array)->toBe([
        'id' => 123,
        'subdomain' => 'blog',
        'location' => 'https://blog.example.com',
        'type' => 'permanent',
        'includePath' => true,
        'wildcard' => false,
    ]);
});

it('generates full url correctly', function (): void {
    $forward = new UrlForward(
        id: 123,
        subdomain: 'api',
        location: 'https://api.example.com',
        type: UrlForwardType::PERMANENT,
        includePath: true,
        wildcard: false,
    );

    expect($forward->fullUrl('example.com'))->toBe('api.example.com');

    $rootForward = new UrlForward(
        id: 124,
        subdomain: '',
        location: 'https://example.com',
        type: UrlForwardType::PERMANENT,
        includePath: true,
        wildcard: false,
    );

    expect($rootForward->fullUrl('example.com'))->toBe('example.com');
});

it('detects forward types correctly', function (): void {
    $permanent = new UrlForward(
        id: 1,
        subdomain: 'www',
        location: 'https://example.com',
        type: UrlForwardType::PERMANENT,
        includePath: true,
        wildcard: false,
    );

    expect($permanent->isPermanent)->toBeTrue()
        ->and($permanent->isTemporary)->toBeFalse();

    $temporary = new UrlForward(
        id: 2,
        subdomain: 'www',
        location: 'https://example.com',
        type: UrlForwardType::TEMPORARY,
        includePath: true,
        wildcard: false,
    );

    expect($temporary->isPermanent)->toBeFalse()
        ->and($temporary->isTemporary)->toBeTrue();
});

it('throws InvalidArgumentException for unknown forward type', function (): void {
    expect(fn (): UrlForward => UrlForward::fromArray([
        'id' => '1',
        'subdomain' => 'www',
        'location' => 'https://example.com',
        'type' => 'unknown_type',
        'includePath' => 'no',
        'wildcard' => 'no',
    ]))->toThrow(InvalidArgumentException::class, "Unknown URL forward type: 'unknown_type'");
});

it('uses UrlForwardType enum', function (): void {
    $forward = UrlForward::fromArray([
        'id' => '1',
        'subdomain' => 'www',
        'location' => 'https://example.com',
        'type' => 'permanent',
        'includePath' => 'no',
        'wildcard' => 'no',
    ]);

    expect($forward->type)->toBeInstanceOf(UrlForwardType::class)
        ->and($forward->type)->toBe(UrlForwardType::PERMANENT)
        ->and($forward->type->value)->toBe('permanent');
});
