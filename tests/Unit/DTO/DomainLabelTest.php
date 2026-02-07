<?php

declare(strict_types=1);

use Porkbun\DTO\DomainLabel;

test('it creates domain label from array', function (): void {
    $domainLabel = DomainLabel::fromArray([
        'id' => '123',
        'title' => 'Production',
        'color' => '#FF5733',
    ]);

    expect($domainLabel->id)->toBe('123')
        ->and($domainLabel->title)->toBe('Production')
        ->and($domainLabel->color)->toBe('#FF5733');
});

test('it handles missing data with defaults', function (): void {
    $domainLabel = DomainLabel::fromArray([]);

    expect($domainLabel->id)->toBe('')
        ->and($domainLabel->title)->toBe('')
        ->and($domainLabel->color)->toBe('');
});

test('it converts to array', function (): void {
    $label = new DomainLabel(
        id: '456',
        title: 'Staging',
        color: '#00FF00',
    );

    expect($label->toArray())->toBe([
        'id' => '456',
        'title' => 'Staging',
        'color' => '#00FF00',
    ]);
});

test('it casts numeric values to strings', function (): void {
    $domainLabel = DomainLabel::fromArray([
        'id' => 789,
        'title' => 'Test',
        'color' => '#000000',
    ]);

    expect($domainLabel->id)->toBe('789');
});
