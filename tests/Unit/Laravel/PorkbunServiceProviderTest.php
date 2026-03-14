<?php

declare(strict_types=1);

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Facade;
use Porkbun\Client;
use Porkbun\Laravel\Facades\Porkbun;
use Porkbun\Laravel\PorkbunServiceProvider;

test('provides returns correct bindings', function (): void {
    $mock = Mockery::mock(Application::class);
    $provider = new PorkbunServiceProvider($mock);

    expect($provider->provides())->toBe([Client::class, 'porkbun']);
});

test('provider implements DeferrableProvider', function (): void {
    $reflection = new ReflectionClass(PorkbunServiceProvider::class);

    expect($reflection->implementsInterface(DeferrableProvider::class))->toBeTrue()
        ->and($reflection->isFinal())->toBeTrue();
});

test('facade accessor returns Client class string', function (): void {
    $reflection = new ReflectionMethod(Porkbun::class, 'getFacadeAccessor');

    expect($reflection->invoke(null))->toBe(Client::class);
});

test('facade class is final', function (): void {
    $reflection = new ReflectionClass(Porkbun::class);

    $parent = $reflection->getParentClass();

    expect($reflection->isFinal())->toBeTrue()
        ->and($parent)->not->toBeFalse()
        ->and($parent !== false ? $parent->getName() : '')->toBe(Facade::class);
});

test('config file exists and has expected structure', function (): void {
    $configPath = __DIR__ . '/../../../src/Laravel/config/porkbun.php';

    expect(file_exists($configPath))->toBeTrue();

    $content = file_get_contents($configPath);

    expect($content)->toContain("'api_key'")
        ->and($content)->toContain("'secret_key'")
        ->and($content)->toContain("'endpoint'")
        ->and($content)->toContain('PORKBUN_API_KEY')
        ->and($content)->toContain('PORKBUN_SECRET_KEY')
        ->and($content)->toContain('PORKBUN_ENDPOINT');
});
