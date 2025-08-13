<?php

declare(strict_types=1);

use Porkbun\Exception\ApiException;
use Porkbun\Exception\AuthenticationException;
use Porkbun\Exception\ExceptionInterface;
use Porkbun\Exception\InvalidArgumentException;
use Porkbun\Exception\NetworkException;
use Porkbun\Exception\PorkbunApiException;
use Porkbun\Exception\RuntimeException;
use Porkbun\Exception\ValidationException;

test('porkbun api exception is abstract', function (): void {
    $reflection = new ReflectionClass(PorkbunApiException::class);
    expect($reflection->isAbstract())->toBeTrue();
});

test('api exception can be instantiated', function (): void {
    $exception = new ApiException('Test message', 404);

    expect($exception)->toBeInstanceOf(ApiException::class);
    expect($exception)->toBeInstanceOf(PorkbunApiException::class);
    expect($exception)->toBeInstanceOf(ExceptionInterface::class);
    expect($exception->getMessage())->toBe('Test message');
    expect($exception->getStatusCode())->toBe(404);
});

test('api exception with previous exception', function (): void {
    $previous = new Exception('Previous exception');
    $exception = new ApiException('Test message', 500, $previous);

    expect($exception->getPrevious())->toBe($previous);
    expect($exception->getStatusCode())->toBe(500);
});

test('authentication exception can be instantiated', function (): void {
    $exception = new AuthenticationException('Auth failed');

    expect($exception)->toBeInstanceOf(AuthenticationException::class);
    expect($exception)->toBeInstanceOf(PorkbunApiException::class);
    expect($exception)->toBeInstanceOf(ExceptionInterface::class);
    expect($exception->getMessage())->toBe('Auth failed');
});

test('network exception can be instantiated', function (): void {
    $exception = new NetworkException('Network failed');

    expect($exception)->toBeInstanceOf(NetworkException::class);
    expect($exception)->toBeInstanceOf(PorkbunApiException::class);
    expect($exception)->toBeInstanceOf(ExceptionInterface::class);
    expect($exception->getMessage())->toBe('Network failed');
});

test('validation exception can be instantiated', function (): void {
    $exception = new ValidationException('Validation failed');

    expect($exception)->toBeInstanceOf(ValidationException::class);
    expect($exception)->toBeInstanceOf(PorkbunApiException::class);
    expect($exception)->toBeInstanceOf(ExceptionInterface::class);
    expect($exception->getMessage())->toBe('Validation failed');
});

test('invalid argument exception can be instantiated', function (): void {
    $exception = new InvalidArgumentException('Invalid argument');

    expect($exception)->toBeInstanceOf(InvalidArgumentException::class);
    expect($exception)->toBeInstanceOf(\InvalidArgumentException::class);
    expect($exception)->toBeInstanceOf(ExceptionInterface::class);
    expect($exception->getMessage())->toBe('Invalid argument');
});

test('runtime exception can be instantiated', function (): void {
    $exception = new RuntimeException('Runtime error');

    expect($exception)->toBeInstanceOf(RuntimeException::class);
    expect($exception)->toBeInstanceOf(\RuntimeException::class);
    expect($exception)->toBeInstanceOf(ExceptionInterface::class);
    expect($exception->getMessage())->toBe('Runtime error');
});

test('all exceptions implement exception interface', function (): void {
    $porkbunExceptions = [
        ApiException::class,
        AuthenticationException::class,
        NetworkException::class,
        ValidationException::class,
    ];

    $phpExceptions = [
        InvalidArgumentException::class,
        RuntimeException::class,
    ];

    // Test Porkbun specific exceptions
    foreach ($porkbunExceptions as $porkbunException) {
        $reflection = new ReflectionClass($porkbunException);
        expect($reflection->implementsInterface(ExceptionInterface::class))->toBeTrue();
        expect($reflection->isSubclassOf(PorkbunApiException::class))->toBeTrue();
    }

    // Test PHP built-in exceptions that implement our interface
    foreach ($phpExceptions as $phpException) {
        $reflection = new ReflectionClass($phpException);
        expect($reflection->implementsInterface(ExceptionInterface::class))->toBeTrue();
        expect($reflection->isSubclassOf(Exception::class))->toBeTrue();
    }
});
