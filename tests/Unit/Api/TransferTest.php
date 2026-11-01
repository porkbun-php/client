<?php

declare(strict_types=1);

use Porkbun\Api\Transfer;
use Porkbun\DTO\DomainTransfer;
use Porkbun\DTO\TransferAuthCodeUpdate;
use Porkbun\DTO\TransferCancellation;
use Porkbun\DTO\TransferRecord;
use Porkbun\Exception\ApiException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

test('transfer api initiates a transfer', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect($request->getMethod())->toBe('POST')
                ->and((string) $request->getUri())->toBe('https://api.porkbun.com/api/json/v3/domain/transfer/example.com')
                ->and(json_decode((string) $request->getBody(), true))->toBe([
                    'authCode' => 'Ab3$cd-EF9',
                    'cost' => 1108,
                    'apikey' => 'pk1_key',
                    'secretapikey' => 'sk1_secret',
                ]);

            return true;
        }))
        ->andReturn(createMockResponse('{"status":"SUCCESS","domain":"example.com","orderId":9915398,"transferId":788793,"message":"Transfer completed. In the sandbox the transfer is simulated as completing immediately, so the domain is already active and manageable (production transfers typically take 5-7 days).","limits":{"attempts":{"TTL":1,"limit":1,"used":1,"naturalLanguage":"1 out of 1 transfer attempts within 1 seconds used."},"success":{"TTL":86400,"limit":1000,"used":1,"naturalLanguage":"1 out of 1000 successful transfers within 86400 seconds used."}},"balance":85596,"ttlRemaining":86399,"requestId":"00000000-0000-0000-0000-000000000000","sandbox":true}'));

    $transfer = new Transfer(createMockContext(createHttpClient($mock, 'pk1_key', 'sk1_secret')), 'example.com');

    $domainTransfer = $transfer->initiate('Ab3$cd-EF9', 1108);

    expect($domainTransfer)->toBeInstanceOf(DomainTransfer::class)
        ->and($domainTransfer->domain)->toBe('example.com')
        ->and($domainTransfer->orderId)->toBe(9915398)
        ->and($domainTransfer->transferId)->toBe(788793)
        ->and($domainTransfer->balanceInCents)->toBe(85596)
        ->and($domainTransfer->remainingTransfers)->toBe(999)
        ->and($domainTransfer->message)->toStartWith('Transfer completed.')
        ->and(json_encode($domainTransfer))->not->toContain('Ab3$cd-EF9');
});

test('transfer api initiate throws when the response has no order', function (): void {
    $httpClient = createHttpClient(createMockHttpClient([[
        'status' => 'SUCCESS',
        'dryRun' => true,
        'wouldSucceed' => true,
        'operation' => 'transfer',
        'domain' => 'example.com',
        'cost' => 1108,
        'balance' => 86704,
    ]]), 'pk1_key', 'sk1_secret');
    $transfer = new Transfer(createMockContext($httpClient), 'example.com');

    expect(fn (): DomainTransfer => $transfer->initiate('Ab3$cd-EF9', 1108))
        ->toThrow(ApiException::class, 'Invalid API response: missing transfer order');
});

test('transfer api initiate throws on a cost mismatch', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->once()->andReturn(createMockResponse('{"status":"ERROR","code":"COST_MISMATCH","message":"The cost submitted does not match the transfer cost. Expected: 1108 cents ($11.08). Read it from GET \/domain\/checkDomain\/example.com, or send \"dryRun\": true with \"cost\": 0 for a quote.","next_action":{"type":"fix_request","retryable":false},"requestId":"00000000-0000-0000-0000-000000000000","sandbox":true}', 400));

    $transfer = new Transfer(createMockContext(createHttpClient($mock, 'pk1_key', 'sk1_secret')), 'example.com');
    $e = catchPorkbunException(fn (): DomainTransfer => $transfer->initiate('Ab3$cd-EF9', 1));

    expect($e)->toBeInstanceOf(ApiException::class)
        ->and($e->getStatusCode())->toBe(400)
        ->and($e->getErrorCode())->toBe('COST_MISMATCH');
});

test('transfer api gets the transfer details', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect($request->getMethod())->toBe('GET')
                ->and((string) $request->getUri())->toBe('https://api.porkbun.com/api/json/v3/domain/getTransfer/example.com')
                ->and($request->getHeaderLine('X-API-Key'))->toBe('pk1_key')
                ->and($request->getHeaderLine('X-Secret-API-Key'))->toBe('sk1_secret');

            return true;
        }))
        ->andReturn(createMockResponse('{"status":"SUCCESS","transfer":{"domain":"example.com","status":"DONE","statusDescription":"Transfer completed.","transferDate":"2026-11-01 11:04:28","orderId":"9915398"},"requestId":"00000000-0000-0000-0000-000000000000","sandbox":true}'));

    $transfer = new Transfer(createMockContext(createHttpClient($mock, 'pk1_key', 'sk1_secret')), 'example.com');

    $record = $transfer->details();

    expect($record)->toBeInstanceOf(TransferRecord::class)
        ->and($record->domain)->toBe('example.com')
        ->and($record->status)->toBe('DONE')
        ->and($record->statusDescription)->toBe('Transfer completed.')
        ->and($record->orderId)->toBe(9915398)
        ->and($record->transferDate?->format(DATE_ATOM))->toBe('2026-11-01T11:04:28+00:00');
});

test('transfer api details throws when the response has no transfer', function (): void {
    $httpClient = createHttpClient(createMockHttpClient([['status' => 'SUCCESS']]), 'pk1_key', 'sk1_secret');
    $transfer = new Transfer(createMockContext($httpClient), 'example.com');

    expect(fn (): TransferRecord => $transfer->details())
        ->toThrow(ApiException::class, 'Invalid API response: missing transfer');
});

test('transfer api details throws when there is no transfer', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->once()->andReturn(createMockResponse('{"status":"ERROR","message":"No transfer found for that domain.","code":"TRANSFER_NOT_FOUND","next_action":{"type":"fix_request","hint":"No pending inbound transfer for that domain in this account. Start one with POST \/domain\/transfer\/{domain}.","retryable":false},"requestId":"00000000-0000-0000-0000-000000000000","sandbox":true}', 400));

    $transfer = new Transfer(createMockContext(createHttpClient($mock, 'pk1_key', 'sk1_secret')), 'example.com');
    $e = catchPorkbunException(fn (): TransferRecord => $transfer->details());

    expect($e)->toBeInstanceOf(ApiException::class)
        ->and($e->getMessage())->toBe('No transfer found for that domain.')
        ->and($e->getErrorCode())->toBe('TRANSFER_NOT_FOUND');
});

test('transfer api cancels a transfer', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect($request->getMethod())->toBe('POST')
                ->and((string) $request->getUri())->toBe('https://api.porkbun.com/api/json/v3/domain/cancelTransfer/example.com')
                ->and(json_decode((string) $request->getBody(), true))->toBe([
                    'apikey' => 'pk1_key',
                    'secretapikey' => 'sk1_secret',
                ]);

            return true;
        }))
        ->andReturn(createMockResponse('{"status":"SUCCESS","domain":"example.com","cancelled":true,"previousStatus":"PENDINGTRANSFER","transferStatus":"CANCELED","withdrawnAtRegistry":true,"registryResultCode":"1000","registryCode":"1000","refunded":true,"refundAmount":1108,"orderId":9915398,"message":"Transfer cancelled and refunded."}'));

    $transfer = new Transfer(createMockContext(createHttpClient($mock, 'pk1_key', 'sk1_secret')), 'example.com');

    $cancellation = $transfer->cancel();

    expect($cancellation)->toBeInstanceOf(TransferCancellation::class)
        ->and($cancellation->domain)->toBe('example.com')
        ->and($cancellation->cancelled)->toBeTrue()
        ->and($cancellation->withdrawnAtRegistry)->toBeTrue()
        ->and($cancellation->refunded)->toBeTrue()
        ->and($cancellation->refundAmountInCents)->toBe(1108)
        ->and($cancellation->orderId)->toBe(9915398)
        ->and($cancellation->transferStatus)->toBe('CANCELED');
});

test('transfer api cancel throws when there is no pending transfer', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $mock->shouldReceive('sendRequest')->once()->andReturn(createMockResponse('{"status":"ERROR","code":"TRANSFER_NOT_FOUND","message":"No pending inbound transfer for example.com in this account. Start one with POST \/domain\/transfer\/example.com.","next_action":{"type":"fix_request","hint":"No pending inbound transfer for that domain in this account. Start one with POST \/domain\/transfer\/{domain}.","retryable":false},"requestId":"00000000-0000-0000-0000-000000000000","sandbox":true}', 400));

    $transfer = new Transfer(createMockContext(createHttpClient($mock, 'pk1_key', 'sk1_secret')), 'example.com');
    $e = catchPorkbunException(fn (): TransferCancellation => $transfer->cancel());

    expect($e)->toBeInstanceOf(ApiException::class)
        ->and($e->getErrorCode())->toBe('TRANSFER_NOT_FOUND');
});

test('transfer api updates the auth code', function (): void {
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request): bool {
            expect($request->getMethod())->toBe('POST')
                ->and((string) $request->getUri())->toBe('https://api.porkbun.com/api/json/v3/domain/updateTransferAuthCode/example.com')
                ->and(json_decode((string) $request->getBody(), true))->toBe([
                    'authCode' => 'Ab3$cd-EF9',
                    'apikey' => 'pk1_key',
                    'secretapikey' => 'sk1_secret',
                ]);

            return true;
        }))
        ->andReturn(createMockResponse('{"status":"SUCCESS","domain":"example.com","authCodeValid":true,"previousStatus":"PENDINGAUTH","transferStatus":"PENDINGSUBMIT","message":"Auth code replaced and transfer re-queued."}'));

    $transfer = new Transfer(createMockContext(createHttpClient($mock, 'pk1_key', 'sk1_secret')), 'example.com');

    $update = $transfer->updateAuthCode('Ab3$cd-EF9');

    expect($update)->toBeInstanceOf(TransferAuthCodeUpdate::class)
        ->and($update->domain)->toBe('example.com')
        ->and($update->authCodeValid)->toBeTrue()
        ->and($update->previousStatus)->toBe('PENDINGAUTH')
        ->and($update->transferStatus)->toBe('PENDINGSUBMIT')
        ->and(json_encode($update))->not->toContain('Ab3$cd-EF9');
});
