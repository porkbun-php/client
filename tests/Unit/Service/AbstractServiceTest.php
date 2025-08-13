<?php

declare(strict_types=1);

use Porkbun\Config;
use Porkbun\Exception\ApiException;
use Porkbun\Exception\AuthenticationException;
use Porkbun\Exception\NetworkException;
use Porkbun\Service\AbstractService;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;

// Concrete implementation for testing
class TestService extends AbstractService
{
    public function testRequest(string $method, string $endpoint, array $data = []): array
    {
        return $this->request($method, $endpoint, $data);
    }

    public function testPost(string $endpoint, array $data = []): array
    {
        return $this->post($endpoint, $data);
    }

    public function testGet(string $endpoint, array $data = []): array
    {
        return $this->get($endpoint, $data);
    }

    protected function requiresAuth(): bool
    {
        return true;
    }
}

class TestServiceNoAuth extends AbstractService
{
    public function testRequest(string $method, string $endpoint, array $data = []): array
    {
        return $this->request($method, $endpoint, $data);
    }

    public function testPost(string $endpoint, array $data = []): array
    {
        return $this->post($endpoint, $data);
    }

    public function testGet(string $endpoint, array $data = []): array
    {
        return $this->get($endpoint, $data);
    }

    protected function requiresAuth(): bool
    {
        return false;
    }
}

test('abstract service handles successful response', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = ['status' => 'SUCCESS', 'data' => 'test'];
    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new TestService($mock, $config);
    $result = $service->testRequest('POST', '/test', ['param' => 'value']);

    expect($result)->toBe($responseData);
});

test('abstract service throws authentication exception for 403', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();

    $response = mockApiResponse('');
    $response->shouldReceive('getStatusCode')->andReturn(403);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new TestService($mock, $config);

    expect(fn (): array => $service->testRequest('POST', '/test'))
        ->toThrow(AuthenticationException::class, 'Authentication required or invalid');
});

test('abstract service throws api exception for other 4xx/5xx', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();

    $response = mockApiResponse('');
    $response->shouldReceive('getStatusCode')->andReturn(404);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new TestService($mock, $config);

    expect(fn (): array => $service->testRequest('POST', '/test'))
        ->toThrow(ApiException::class, 'HTTP 404');
});

test('abstract service throws api exception for http error with api message', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();

    $responseData = ['status' => 'ERROR', 'message' => 'Invalid API key. (002)'];
    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(400);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new TestService($mock, $config);

    expect(fn (): array => $service->testRequest('POST', '/test'))
        ->toThrow(ApiException::class, 'HTTP 400: Invalid API key. (002)');
});

test('abstract service throws authentication exception for 403 with api message', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();

    $responseData = ['status' => 'ERROR', 'message' => 'Invalid credentials'];
    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(403);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new TestService($mock, $config);

    expect(fn (): array => $service->testRequest('POST', '/test'))
        ->toThrow(AuthenticationException::class, 'Authentication required or invalid: Invalid credentials');
});

test('abstract service throws network exception for http client errors', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();

    $clientException = new class () extends Exception implements ClientExceptionInterface {};

    $mock->shouldReceive('sendRequest')->once()->andThrow($clientException);

    $service = new TestService($mock, $config);

    expect(fn (): array => $service->testRequest('POST', '/test'))
        ->toThrow(NetworkException::class);
});

test('abstract service throws api exception for error status', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();

    $responseData = ['status' => 'ERROR', 'message' => 'API error message'];
    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new TestService($mock, $config);

    expect(fn (): array => $service->testRequest('POST', '/test'))
        ->toThrow(ApiException::class, 'API error message');
});

test('abstract service throws api exception for invalid response format', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();

    $response = mockApiResponse('invalid json');
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new TestService($mock, $config);

    expect(fn (): array => $service->testRequest('POST', '/test'))
        ->toThrow(ApiException::class, 'Invalid API response format');
});

test('abstract service adds auth payload when required and available', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = ['status' => 'SUCCESS'];
    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    // Verify auth payload is included in request
    $mock->shouldReceive('sendRequest')
        ->with(Mockery::on(function ($request): bool {
            $body = json_decode($request->getBody()->getContents(), true);

            return is_array($body)
                && isset($body['apikey']) && isset($body['secretapikey'])
                && $body['apikey'] === 'pk1_key'
                && $body['secretapikey'] === 'sk1_secret'
                && $body['param'] === 'value';
        }))
        ->once()
        ->andReturn($response);

    $service = new TestService($mock, $config);
    $service->testRequest('POST', '/test', ['param' => 'value']);
});

test('abstract service does not add auth payload when not required', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();
    $config->setAuth('pk1_key', 'sk1_secret');

    $responseData = ['status' => 'SUCCESS'];
    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    // Verify auth payload is NOT included in request
    $mock->shouldReceive('sendRequest')
        ->with(Mockery::on(function ($request): bool {
            $body = json_decode($request->getBody()->getContents(), true);

            return is_array($body)
                && !isset($body['apikey']) && !isset($body['secretapikey'])
                && $body['param'] === 'value';
        }))
        ->once()
        ->andReturn($response);

    $service = new TestServiceNoAuth($mock, $config);
    $service->testRequest('POST', '/test', ['param' => 'value']);
});

test('abstract service post method works', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();

    $responseData = ['status' => 'SUCCESS'];
    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new TestServiceNoAuth($mock, $config);
    $result = $service->testPost('/test', ['param' => 'value']);

    expect($result)->toBe($responseData);
});

test('abstract service get method works', function (): void {
    $mock = Mockery::mock(ClientInterface::class);
    $config = new Config();

    $responseData = ['status' => 'SUCCESS'];
    $response = mockApiResponse(json_encode($responseData));
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $mock->shouldReceive('sendRequest')->once()->andReturn($response);

    $service = new TestServiceNoAuth($mock, $config);
    $result = $service->testGet('/test', ['param' => 'value']);

    expect($result)->toBe($responseData);
});
