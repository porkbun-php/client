<?php

declare(strict_types=1);

namespace Porkbun\Laravel;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Override;
use Porkbun\Client;

final class PorkbunServiceProvider extends ServiceProvider implements DeferrableProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/porkbun.php', 'porkbun');

        $this->app->singleton(Client::class, function ($app): Client {
            /** @var array<string, mixed> $config */
            $config = $app['config']['porkbun'];

            /** @var string|null $apiKey */
            $apiKey = $config['api_key'] ?? null;
            /** @var string|null $secretKey */
            $secretKey = $config['secret_key'] ?? null;

            $client = new Client();

            if ($apiKey !== null && $secretKey !== null) {
                $client->authenticate($apiKey, $secretKey);
            }

            $endpoint = $config['endpoint'] ?? 'default';
            if ($endpoint === 'ipv4') {
                $client->useIpv4Endpoint();
            }

            return $client;
        });

        $this->app->alias(Client::class, 'porkbun');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/porkbun.php' => $this->app->configPath('porkbun.php'),
            ], 'porkbun-config');
        }
    }

    /**
     * @return array<int, class-string|string>
     */
    #[Override]
    public function provides(): array
    {
        return [
            Client::class,
            'porkbun',
        ];
    }
}
