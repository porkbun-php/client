<?php

declare(strict_types=1);

namespace Porkbun;

class Config
{
    public function __construct(
        private string $baseUrl = 'https://api.porkbun.com/api/json/v3',
        private ?string $apiKey = null,
        private ?string $secretKey = null,
        private int $timeout = 30
    ) {
    }

    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function setAuth(string $apiKey, string $secretKey): void
    {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
    }

    public function clearAuth(): void
    {
        $this->apiKey = null;
        $this->secretKey = null;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function hasAuth(): bool
    {
        return $this->apiKey !== null && $this->secretKey !== null;
    }

    public function getAuthPayload(): array
    {
        if (!$this->hasAuth()) {
            return [];
        }

        return [
            'apikey' => $this->apiKey,
            'secretapikey' => $this->secretKey,
        ];
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }
}
