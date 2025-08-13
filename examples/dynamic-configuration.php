<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Porkbun\Client;
use Porkbun\Config;

// Example 1: Flexible client initialization
echo "=== Dynamic Configuration Examples ===\n\n";

// Start with no configuration
$client = new Client();
echo "1. Created client with default config\n";
echo "   Base URL: " . $client->getConfig()->getBaseUrl() . "\n";
echo "   Has auth: " . ($client->getConfig()->hasAuth() ? 'Yes' : 'No') . "\n\n";

// Change base URL for IPv4-only endpoint
$client->setBaseUrl('https://api-ipv4.porkbun.com/api/json/v3');
echo "2. Switched to IPv4-only endpoint\n";
echo "   New base URL: " . $client->getConfig()->getBaseUrl() . "\n\n";

// Add authentication when needed
$client->setAuth('pk1_your_key', 'sk1_your_secret');
echo "3. Added authentication\n";
echo "   Has auth: " . ($client->getConfig()->hasAuth() ? 'Yes' : 'No') . "\n\n";

// Example 2: Multi-account management
echo "=== Multi-Account Management ===\n\n";

// Switch between different accounts
$accounts = [
    'account1' => ['pk1_account1_key', 'sk1_account1_secret'],
    'account2' => ['pk1_account2_key', 'sk1_account2_secret'],
];

foreach ($accounts as $name => $credentials) {
    $client->setAuth($credentials[0], $credentials[1]);
    echo "Switched to $name\n";

    try {
        // This would normally make API calls
        echo "  API key configured: " . substr($credentials[0], 0, 10) . "...\n";
    } catch (\Exception $e) {
        echo "  Error with $name: " . $e->getMessage() . "\n";
    }
}

// Go back to public-only mode
$client->clearAuth();
echo "\nCleared authentication - back to public-only mode\n";
echo "Has auth: " . ($client->getConfig()->hasAuth() ? 'Yes' : 'No') . "\n\n";

// Example 3: Environment-based configuration
echo "=== Environment-Based Configuration ===\n\n";

class PorkbunManager
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->configureForEnvironment();
    }

    private function configureForEnvironment(): void
    {
        $env = $_ENV['APP_ENV'] ?? 'production';

        switch ($env) {
            case 'testing':
                $this->client->setBaseUrl('https://api-test.porkbun.com/api/json/v3');
                echo "Configured for testing environment\n";

                break;
            case 'ipv4-only':
                $this->client->setBaseUrl('https://api-ipv4.porkbun.com/api/json/v3');
                echo "Configured for IPv4-only environment\n";

                break;
            default:
                $this->client->setBaseUrl('https://api.porkbun.com/api/json/v3');
                echo "Configured for production environment\n";
        }

        if (isset($_ENV['PORKBUN_API_KEY'])) {
            $this->client->setAuth($_ENV['PORKBUN_API_KEY'], $_ENV['PORKBUN_SECRET']);
            echo "Authentication loaded from environment\n";
        }
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}

$manager = new PorkbunManager();
$configuredClient = $manager->getClient();
echo "Final base URL: " . $configuredClient->getConfig()->getBaseUrl() . "\n";

// Example 4: Initial configuration with Config object
echo "\n=== Initial Configuration ===\n\n";

$config = new Config(
    baseUrl: 'https://custom-api.example.com/v3',
    apiKey: 'pk1_initial_key',
    secretKey: 'sk1_initial_secret'
);

$preconfiguredClient = new Client($config);
echo "Created client with initial config:\n";
echo "  Base URL: " . $preconfiguredClient->getConfig()->getBaseUrl() . "\n";
echo "  Has auth: " . ($preconfiguredClient->getConfig()->hasAuth() ? 'Yes' : 'No') . "\n";

// Can still change configuration dynamically
$preconfiguredClient->setBaseUrl('https://api.porkbun.com/api/json/v3');
echo "  Updated base URL: " . $preconfiguredClient->getConfig()->getBaseUrl() . "\n";
