<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\Internal\ClientContext;

final class AutoRenew extends AbstractApi
{
    public function __construct(
        ClientContext $clientContext,
        private readonly string $domain,
    ) {
        parent::__construct($clientContext);
    }

    public function enable(): bool
    {
        return $this->update(true);
    }

    public function disable(): bool
    {
        return $this->update(false);
    }

    private function update(bool $enable): bool
    {
        $response = $this->post(
            "/domain/updateAutoRenew/{$this->domain}",
            ['status' => $enable ? 'on' : 'off']
        );

        $results = $response['results'] ?? [];

        return isset($results[$this->domain]['status'])
            && $results[$this->domain]['status'] === 'SUCCESS';
    }
}
