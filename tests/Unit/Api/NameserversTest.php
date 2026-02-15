<?php

declare(strict_types=1);

use Porkbun\Api\Nameservers;
use Porkbun\DTO\NameserverCollection;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

test('nameservers api can get all nameservers', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'ns' => ['ns1.porkbun.com', 'ns2.porkbun.com'],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $nameservers = new Nameservers(createMockContext($httpClient), 'example.com');

    $nameserverCollection = $nameservers->all();

    expect($nameserverCollection)->toBeInstanceOf(NameserverCollection::class)
        ->and($nameserverCollection)->toHaveCount(2)
        ->and($nameserverCollection->first())->toBe('ns1.porkbun.com')
        ->and($nameserverCollection->last())->toBe('ns2.porkbun.com')
        ->and($nameserverCollection->contains('ns1.porkbun.com'))->toBeTrue()
        ->and($nameserverCollection->contains('ns3.porkbun.com'))->toBeFalse();
});

test('nameservers api can update nameservers', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/domain/updateNs/example.com');

            $body = json_decode((string) $request->getBody(), true);
            expect($body)->toHaveKey('ns1', 'ns1.provider.com')
                ->and($body)->toHaveKey('ns2', 'ns2.provider.com');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $nameservers = new Nameservers(createMockContext($httpClient), 'example.com');

    $nameservers->update(['ns1.provider.com', 'ns2.provider.com']);
});

test('nameservers api returns empty collection', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'ns' => []],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $nameservers = new Nameservers(createMockContext($httpClient), 'example.com');

    $nameserverCollection = $nameservers->all();

    expect($nameserverCollection)->toBeInstanceOf(NameserverCollection::class)
        ->and($nameserverCollection->isEmpty())->toBeTrue()
        ->and($nameserverCollection->first())->toBeNull()
        ->and($nameserverCollection->last())->toBeNull();
});
