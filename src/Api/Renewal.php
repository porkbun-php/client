<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\DomainRenewal;
use Porkbun\Internal\ClientContext;

final class Renewal extends AbstractApi
{
    public function __construct(
        ClientContext $clientContext,
        private readonly string $domain,
    ) {
        parent::__construct($clientContext);
    }

    /**
     * @param int $costInCents Expected cost in cents (from availability check)
     */
    public function renew(int $costInCents): DomainRenewal
    {
        $response = $this->post("/domain/renew/{$this->domain}", [
            'cost' => $costInCents,
        ]);

        return DomainRenewal::fromArray($response);
    }
}
