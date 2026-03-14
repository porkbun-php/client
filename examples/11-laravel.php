<?php

/**
 * Laravel integration examples.
 *
 * This file is NOT runnable standalone — it shows usage patterns
 * within a Laravel application.
 *
 * Setup:
 *   composer require porkbun-php/client
 *   php artisan vendor:publish --tag=porkbun-config
 *
 * .env:
 *   PORKBUN_API_KEY=pk1_your_key
 *   PORKBUN_SECRET_KEY=sk1_your_secret
 *   PORKBUN_ENDPOINT=default
 */

declare(strict_types=1);

// ── Facade Usage ────────────────────────────────────────────

use Porkbun\Client;
// All Client methods are available through the facade
// Porkbun::ping();
// Porkbun::domains()->list();
// Porkbun::domain('example.com')->dns()->all();
// Porkbun::domain('example.com')->ssl();
// Porkbun::pricing()->all();

// Runtime endpoint switching
// Porkbun::useIpv4Endpoint();
// Porkbun::useDefaultEndpoint();

// ── Dependency Injection ────────────────────────────────────

use Porkbun\Laravel\Facades\Porkbun;

// The Client is registered as a singleton in the container.
// Inject it in controllers, commands, jobs, etc.

// class DnsController extends Controller
// {
//     public function index(Client $client, string $domain)
//     {
//         return $client->domain($domain)->dns()->all();
//     }
//
//     public function show(Client $client, string $domain, int $id)
//     {
//         return $client->domain($domain)->dns()->find($id);
//     }
// }

// ── Artisan Command Example ─────────────────────────────────

// class SyncDnsCommand extends Command
// {
//     protected $signature = 'dns:sync {domain}';
//
//     public function handle(Client $client): int
//     {
//         $domain = $this->argument('domain');
//         $records = $client->domain($domain)->dns()->all();
//
//         $this->table(
//             ['Name', 'Type', 'Content', 'TTL'],
//             array_map(fn ($r) => [$r->name, $r->type->value, $r->content, $r->ttl], $records->items())
//         );
//
//         return self::SUCCESS;
//     }
// }
