<?php

declare(strict_types=1);

use Porkbun\DTO\NextAction;

it('creates next action from array', function (): void {
    $nextAction = NextAction::fromArray([
        'type' => 'wait_and_retry',
        'hint' => 'Wait, then retry.',
        'url' => 'https://porkbun.com/account/api',
        'retryable' => true,
    ]);

    expect($nextAction->type)->toBe('wait_and_retry')
        ->and($nextAction->hint)->toBe('Wait, then retry.')
        ->and($nextAction->url)->toBe('https://porkbun.com/account/api')
        ->and($nextAction->retryable)->toBeTrue()
        ->and($nextAction->toArray())->toBe([
            'type' => 'wait_and_retry',
            'hint' => 'Wait, then retry.',
            'url' => 'https://porkbun.com/account/api',
            'retryable' => true,
        ]);
});

it('keeps unknown types and defaults missing fields', function (): void {
    $nextAction = NextAction::fromArray(['type' => 'something_new']);

    expect($nextAction->type)->toBe('something_new')
        ->and($nextAction->hint)->toBeNull()
        ->and($nextAction->url)->toBeNull()
        ->and($nextAction->retryable)->toBeFalse();
});
