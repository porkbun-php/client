<?php

declare(strict_types=1);

use Porkbun\DTO\UrlForward;
use Porkbun\DTO\UrlForwardCollection;

test('fromArray creates collection from array data', function (): void {
    $urlForwardCollection = UrlForwardCollection::fromArray([
        ['id' => '1', 'subdomain' => 'go', 'location' => 'https://example.com', 'type' => 'temporary', 'includePath' => 'no', 'wildcard' => 'no'],
        ['id' => '2', 'subdomain' => 'shop', 'location' => 'https://shop.example.com', 'type' => 'permanent', 'includePath' => 'yes', 'wildcard' => 'no'],
    ]);

    expect($urlForwardCollection)->toHaveCount(2)
        ->and($urlForwardCollection->items())->toHaveCount(2)
        ->and($urlForwardCollection->items()[0])->toBeInstanceOf(UrlForward::class)
        ->and($urlForwardCollection->items()[0]->subdomain)->toBe('go');
});

test('first returns first forward', function (): void {
    $urlForwardCollection = UrlForwardCollection::fromArray([
        ['id' => '1', 'subdomain' => 'go', 'location' => 'https://example.com', 'type' => 'temporary', 'includePath' => 'no', 'wildcard' => 'no'],
        ['id' => '2', 'subdomain' => 'shop', 'location' => 'https://shop.example.com', 'type' => 'permanent', 'includePath' => 'yes', 'wildcard' => 'no'],
    ]);

    expect($urlForwardCollection->first()?->subdomain)->toBe('go');
});

test('first returns null for empty collection', function (): void {
    expect(new UrlForwardCollection()->first())->toBeNull();
});

test('last returns last forward', function (): void {
    $urlForwardCollection = UrlForwardCollection::fromArray([
        ['id' => '1', 'subdomain' => 'go', 'location' => 'https://example.com', 'type' => 'temporary', 'includePath' => 'no', 'wildcard' => 'no'],
        ['id' => '2', 'subdomain' => 'shop', 'location' => 'https://shop.example.com', 'type' => 'permanent', 'includePath' => 'yes', 'wildcard' => 'no'],
    ]);

    expect($urlForwardCollection->last()?->subdomain)->toBe('shop');
});

test('last returns null for empty collection', function (): void {
    expect(new UrlForwardCollection()->last())->toBeNull();
});

test('find returns matching forward by id', function (): void {
    $urlForwardCollection = UrlForwardCollection::fromArray([
        ['id' => '1', 'subdomain' => 'go', 'location' => 'https://example.com', 'type' => 'temporary', 'includePath' => 'no', 'wildcard' => 'no'],
        ['id' => '2', 'subdomain' => 'shop', 'location' => 'https://shop.example.com', 'type' => 'permanent', 'includePath' => 'yes', 'wildcard' => 'no'],
    ]);

    $found = $urlForwardCollection->find(2);

    expect($found)->toBeInstanceOf(UrlForward::class)
        ->and($found?->subdomain)->toBe('shop');

    expect($urlForwardCollection->find(999))->toBeNull();
});

test('has checks for forward existence by id', function (): void {
    $urlForwardCollection = UrlForwardCollection::fromArray([
        ['id' => '1', 'subdomain' => 'go', 'location' => 'https://example.com', 'type' => 'temporary', 'includePath' => 'no', 'wildcard' => 'no'],
    ]);

    expect($urlForwardCollection->has(1))->toBeTrue()
        ->and($urlForwardCollection->has(999))->toBeFalse();
});

test('filter applies callback', function (): void {
    $urlForwardCollection = UrlForwardCollection::fromArray([
        ['id' => '1', 'subdomain' => 'go', 'location' => 'https://example.com', 'type' => 'temporary', 'includePath' => 'no', 'wildcard' => 'no'],
        ['id' => '2', 'subdomain' => 'shop', 'location' => 'https://shop.example.com', 'type' => 'permanent', 'includePath' => 'yes', 'wildcard' => 'no'],
        ['id' => '3', 'subdomain' => 'blog', 'location' => 'https://blog.example.com', 'type' => 'permanent', 'includePath' => 'no', 'wildcard' => 'no'],
    ]);

    $filtered = $urlForwardCollection->filter(fn (UrlForward $urlForward): bool => $urlForward->isPermanent);

    expect($filtered)->toBeInstanceOf(UrlForwardCollection::class)
        ->and($filtered)->toHaveCount(2)
        ->and($filtered->first()?->subdomain)->toBe('shop')
        ->and($filtered->last()?->subdomain)->toBe('blog');
});

test('isEmpty and isNotEmpty', function (): void {
    $empty = new UrlForwardCollection();
    $urlForwardCollection = UrlForwardCollection::fromArray([
        ['id' => '1', 'subdomain' => 'go', 'location' => 'https://example.com', 'type' => 'temporary', 'includePath' => 'no', 'wildcard' => 'no'],
    ]);

    expect($empty->isEmpty())->toBeTrue()
        ->and($empty->isNotEmpty())->toBeFalse()
        ->and($urlForwardCollection->isEmpty())->toBeFalse()
        ->and($urlForwardCollection->isNotEmpty())->toBeTrue();
});

test('collection is countable', function (): void {
    $urlForwardCollection = UrlForwardCollection::fromArray([
        ['id' => '1', 'subdomain' => 'go', 'location' => 'https://example.com', 'type' => 'temporary', 'includePath' => 'no', 'wildcard' => 'no'],
        ['id' => '2', 'subdomain' => 'shop', 'location' => 'https://shop.example.com', 'type' => 'permanent', 'includePath' => 'yes', 'wildcard' => 'no'],
    ]);

    expect($urlForwardCollection->count())->toBe(2)
        ->and(count($urlForwardCollection))->toBe(2);
});

test('collection is iterable', function (): void {
    $urlForwardCollection = UrlForwardCollection::fromArray([
        ['id' => '1', 'subdomain' => 'go', 'location' => 'https://example.com', 'type' => 'temporary', 'includePath' => 'no', 'wildcard' => 'no'],
    ]);

    $items = [];
    foreach ($urlForwardCollection as $forward) {
        $items[] = $forward;
    }

    expect($items)->toHaveCount(1)
        ->and($items[0])->toBeInstanceOf(UrlForward::class);
});

test('toArray and jsonSerialize return same data', function (): void {
    $urlForwardCollection = UrlForwardCollection::fromArray([
        ['id' => '1', 'subdomain' => 'go', 'location' => 'https://example.com', 'type' => 'temporary', 'includePath' => 'no', 'wildcard' => 'no'],
    ]);

    expect($urlForwardCollection->toArray())->toBe($urlForwardCollection->jsonSerialize())
        ->and($urlForwardCollection->toArray()[0])->toHaveKey('subdomain')
        ->and($urlForwardCollection->toArray()[0]['subdomain'])->toBe('go');
});
