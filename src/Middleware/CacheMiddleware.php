<?php

declare(strict_types=1);

namespace Porkbun\Middleware;

use Porkbun\Request\AbstractRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

class CacheMiddleware extends AbstractMiddleware
{
    private CacheInterface $cache;

    private array $cacheableEndpoints;

    public function __construct(CacheInterface $cache, private int $ttl = 300, array $cacheableEndpoints = [])
    {
        $this->cache = $cache;
        $this->cacheableEndpoints = $cacheableEndpoints !== [] ? $cacheableEndpoints : [
            '/pricing/get',
            '/dns/retrieve',
            '/domain/getNs',
        ];
    }

    public function afterResponse(AbstractRequest $apiRequest, ResponseInterface $httpResponse, array $responseData): array
    {
        // Only cache successful responses for cacheable endpoints
        if ($this->shouldCache($apiRequest, $responseData)) {
            $cacheKey = $this->getCacheKey($apiRequest);
            $this->cache->set($cacheKey, $responseData, $this->ttl);
        }

        return $responseData;
    }

    public function getCachedResponse(AbstractRequest $apiRequest): ?array
    {
        if (!$this->shouldCache($apiRequest)) {
            return null;
        }

        $cacheKey = $this->getCacheKey($apiRequest);

        return $this->cache->get($cacheKey);
    }

    private function shouldCache(AbstractRequest $apiRequest, ?array $responseData = null): bool
    {
        // Don't cache if endpoint is not in cacheable list
        if (!in_array($apiRequest->getEndpoint(), $this->cacheableEndpoints, true)) {
            return false;
        }

        // Don't cache failed responses
        if ($responseData !== null && ($responseData['status'] ?? '') !== 'SUCCESS') {
            return false;
        }

        return true;
    }

    private function getCacheKey(AbstractRequest $apiRequest): string
    {
        return 'porkbun_api_' . md5($apiRequest->getEndpoint() . serialize($apiRequest->getData()));
    }

    public function clearCache(AbstractRequest $apiRequest): bool
    {
        $cacheKey = $this->getCacheKey($apiRequest);

        return $this->cache->delete($cacheKey);
    }

    public function addCacheableEndpoint(string $endpoint): void
    {
        if (!in_array($endpoint, $this->cacheableEndpoints, true)) {
            $this->cacheableEndpoints[] = $endpoint;
        }
    }

    public function removeCacheableEndpoint(string $endpoint): void
    {
        $this->cacheableEndpoints = array_filter($this->cacheableEndpoints, fn ($ep): bool => $ep !== $endpoint);
    }
}
