<?php

declare(strict_types=1);

namespace Porkbun\Middleware;

use Porkbun\Exception\RuntimeException;
use Porkbun\Request\AbstractRequest;
use Psr\Http\Message\RequestInterface;

class RateLimitMiddleware extends AbstractMiddleware
{
    private array $requestTimes = [];

    public function __construct(private int $maxRequests = 60, private int $windowSeconds = 60)
    {
    }

    public function beforeRequest(AbstractRequest $apiRequest, RequestInterface $httpRequest): RequestInterface
    {
        $now = time();
        $windowStart = $now - $this->windowSeconds;

        // Clean up old request times
        $this->requestTimes = array_filter($this->requestTimes, fn ($time): bool => $time > $windowStart);

        // Check if we're over the rate limit
        if (count($this->requestTimes) >= $this->maxRequests) {
            $oldestRequest = min($this->requestTimes);
            $waitTime = $oldestRequest + $this->windowSeconds - $now + 1;

            throw new RuntimeException(
                "Rate limit exceeded. Maximum {$this->maxRequests} requests per {$this->windowSeconds} seconds. " .
                "Try again in {$waitTime} seconds."
            );
        }

        // Record this request
        $this->requestTimes[] = $now;

        return $httpRequest;
    }

    public function getRemainingRequests(): int
    {
        return max(0, $this->maxRequests - count($this->requestTimes));
    }

    public function getWindowSeconds(): int
    {
        return $this->windowSeconds;
    }
}
