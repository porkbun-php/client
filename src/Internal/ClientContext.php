<?php

declare(strict_types=1);

namespace Porkbun\Internal;

use Closure;
use Porkbun\HttpClient;

/**
 * @internal This class is not part of the public API and may change without notice.
 */
final readonly class ClientContext
{
    /** @param Closure(): HttpClient $httpClientFactory */
    public function __construct(
        private Closure $httpClientFactory,
    ) {
    }

    public function httpClient(): HttpClient
    {
        return ($this->httpClientFactory)();
    }
}
