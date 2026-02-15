<?php

declare(strict_types=1);

use Porkbun\Api\AutoRenew;

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

        expect($result)->toBeTrue();
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

        expect($result)->toBeTrue();
    });

    it('returns false on failure', function (): void {
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

        expect($result)->toBeFalse();
    });

    it('returns false when results are missing', function (): void {
        $mockClient = createMockHttpClient([
            [
                'status' => 'SUCCESS',
                'results' => [],
            ],
        ]);
        $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');

        $autoRenew = new AutoRenew(createMockContext($httpClient), 'example.com');
        $result = $autoRenew->enable();

        expect($result)->toBeFalse();
    });

});
