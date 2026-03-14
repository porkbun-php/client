<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\AutoRenewResult;
use Porkbun\Internal\ClientContext;

final class AutoRenew extends AbstractApi
{
    public function __construct(
        ClientContext $clientContext,
        private readonly string $domain,
    ) {
        parent::__construct($clientContext);
    }

    public function enable(): AutoRenewResult
    {
        return $this->update(true);
    }

    public function disable(): AutoRenewResult
    {
        return $this->update(false);
    }

    private function update(bool $enable): AutoRenewResult
    {
        $response = $this->post(
            "/domain/updateAutoRenew/{$this->domain}",
            ['status' => $enable ? 'on' : 'off']
        );

        /** @var array<string, array{status: string, message?: string}> $results */
        $results = $response['results'] ?? [];

        return AutoRenewResult::fromResults($results)[0]
            ?? new AutoRenewResult(domain: $this->domain, success: false);
    }
}
