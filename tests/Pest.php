<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses()->afterEach(function (): void {
    Mockery::close();
})->in('Feature');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function mockApiResponse(string $jsonResponse): MockInterface
{
    $mock = Mockery::mock(StreamInterface::class);
    $mock->shouldReceive('getContents')->andReturn($jsonResponse);

    $response = Mockery::mock(ResponseInterface::class);
    $response->shouldReceive('getBody')->andReturn($mock);

    return $response;
}
