<?php

declare(strict_types=1);

use Porkbun\Api\UrlForwarding;
use Porkbun\DTO\OperationResult;
use Porkbun\DTO\UrlForward;
use Porkbun\DTO\UrlForwardCollection;
use Porkbun\Enum\UrlForwardType;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

test('url forwarding api can get all forwards', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'forwards' => [
                ['id' => '1', 'subdomain' => 'www', 'location' => 'https://target.com', 'type' => 'temporary', 'includePath' => 'yes', 'wildcard' => 'no'],
                ['id' => '2', 'subdomain' => 'blog', 'location' => 'https://blog.target.com', 'type' => 'permanent', 'includePath' => 'no', 'wildcard' => 'yes'],
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $urlForwarding = new UrlForwarding(createMockContext($httpClient), 'example.com');

    $urlForwardCollection = $urlForwarding->all();

    $first = $urlForwardCollection->first();
    $last = $urlForwardCollection->last();

    assert($first instanceof UrlForward);
    assert($last instanceof UrlForward);
    expect($urlForwardCollection)->toBeInstanceOf(UrlForwardCollection::class)
        ->and($urlForwardCollection)->toHaveCount(2)
        ->and($first)->toBeInstanceOf(UrlForward::class)
        ->and($first->subdomain)->toBe('www')
        ->and($last->subdomain)->toBe('blog');
});

test('url forwarding api can create forward', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/domain/addUrlForward/example.com');

            $body = json_decode((string) $request->getBody(), true);
            expect($body)->toHaveKey('subdomain', 'www')
                ->and($body)->toHaveKey('location', 'https://target.com')
                ->and($body)->toHaveKey('type', 'temporary')
                ->and($body)->toHaveKey('includePath', 'no')
                ->and($body)->toHaveKey('wildcard', 'no');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $urlForwarding = new UrlForwarding(createMockContext($httpClient), 'example.com');

    $result = $urlForwarding->create('https://target.com', 'temporary', subdomain: 'www');

    expect($result)->toBeInstanceOf(OperationResult::class);
});

test('url forwarding api can delete forward', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect((string) $request->getUri())->toContain('/domain/deleteUrlForward/example.com/123');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $urlForwarding = new UrlForwarding(createMockContext($httpClient), 'example.com');

    $result = $urlForwarding->delete(123);

    expect($result)->toBeInstanceOf(OperationResult::class);
});

test('url forwarding api can create forward with enum type', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            $body = json_decode((string) $request->getBody(), true);
            expect($body)->toHaveKey('type', 'permanent');

            return true;
        }))
        ->andReturn(createMockResponse(json_encode(['status' => 'SUCCESS'])));

    $httpClient = createHttpClient($mock, 'pk1_key', 'sk1_secret');
    $urlForwarding = new UrlForwarding(createMockContext($httpClient), 'example.com');

    $result = $urlForwarding->create('https://target.com', UrlForwardType::PERMANENT, subdomain: 'www');

    expect($result)->toBeInstanceOf(OperationResult::class);
});

test('url forwarding api returns empty collection', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'forwards' => []],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $urlForwarding = new UrlForwarding(createMockContext($httpClient), 'example.com');

    $urlForwardCollection = $urlForwarding->all();

    expect($urlForwardCollection)->toBeInstanceOf(UrlForwardCollection::class)
        ->and($urlForwardCollection->isEmpty())->toBeTrue()
        ->and($urlForwardCollection->first())->toBeNull()
        ->and($urlForwardCollection->last())->toBeNull();
});
