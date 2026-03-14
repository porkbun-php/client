<?php

declare(strict_types=1);

use Porkbun\Api\AutoRenew;
use Porkbun\DTO\AutoRenewResult;

describe('AutoRenew API', function (): void {
    it('can enable auto renew', function (): void {
        $mockClient = createMockHttpClient([
            [
                'status' => 'SUCCESS',
                'results' => [
                    'example.com' => ['status' => 'SUCCESS', 'message' => 'Auto renew status updated.'],
                ],
            ],
        ]);
        $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');

        $autoRenew = new AutoRenew(createMockContext($httpClient), 'example.com');
        $result = $autoRenew->enable();

        expect($result)->toBeInstanceOf(AutoRenewResult::class)
            ->and($result->success)->toBeTrue()
            ->and($result->domain)->toBe('example.com')
            ->and($result->message)->toBe('Auto renew status updated.')
            ->and($result->isFailure)->toBeFalse();
    });

    it('can disable auto renew', function (): void {
        $mockClient = createMockHttpClient([
            [
                'status' => 'SUCCESS',
                'results' => [
                    'example.com' => ['status' => 'SUCCESS', 'message' => 'Auto renew status updated.'],
                ],
            ],
        ]);
        $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');

        $autoRenew = new AutoRenew(createMockContext($httpClient), 'example.com');
        $result = $autoRenew->disable();

        expect($result)->toBeInstanceOf(AutoRenewResult::class)
            ->and($result->success)->toBeTrue()
            ->and($result->domain)->toBe('example.com');
    });

    it('returns failure result on failure', function (): void {
        $mockClient = createMockHttpClient([
            [
                'status' => 'SUCCESS',
                'results' => [
                    'example.com' => ['status' => 'FAILED'],
                ],
            ],
        ]);
        $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');

        $autoRenew = new AutoRenew(createMockContext($httpClient), 'example.com');
        $result = $autoRenew->enable();

        expect($result)->toBeInstanceOf(AutoRenewResult::class)
            ->and($result->success)->toBeFalse()
            ->and($result->isFailure)->toBeTrue();
    });

    it('returns failure result when results are missing', function (): void {
        $mockClient = createMockHttpClient([
            [
                'status' => 'SUCCESS',
                'results' => [],
            ],
        ]);
        $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');

        $autoRenew = new AutoRenew(createMockContext($httpClient), 'example.com');
        $result = $autoRenew->enable();

        expect($result)->toBeInstanceOf(AutoRenewResult::class)
            ->and($result->success)->toBeFalse()
            ->and($result->isFailure)->toBeTrue();
    });

});
