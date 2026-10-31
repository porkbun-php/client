<?php

declare(strict_types=1);

use Porkbun\Api\Renewal;
use Porkbun\DTO\DomainRenewal;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

test('renewal api can renew domain', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/domain/renew/example.com');

            $body = json_decode((string) $request->getBody(), true);
            expect($body['cost'])->toBe(1108);

            return true;
        }))
        ->andReturn(createMockResponse(json_encode([
            'status' => 'SUCCESS',
            'domain' => 'example.com',
            'expirationDate' => '2028-10-30 10:41:05',
            'cost' => 1108,
            'orderId' => 123456,
            'balance' => 5000,
            'limits' => [
                'attempts' => ['limit' => 1, 'used' => 1],
                'success' => ['limit' => 1000, 'used' => 1],
            ],
        ])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $renewal = new Renewal(createMockContext($httpClient), 'example.com');

    $domainRenewal = $renewal->renew(1108);

    expect($domainRenewal)->toBeInstanceOf(DomainRenewal::class)
        ->and($domainRenewal->domain)->toBe('example.com')
        ->and($domainRenewal->costInCents)->toBe(1108)
        ->and($domainRenewal->orderId)->toBe(123456)
        ->and($domainRenewal->balanceInCents)->toBe(5000)
        ->and($domainRenewal->expirationDate?->format('Y-m-d'))->toBe('2028-10-30');
});
