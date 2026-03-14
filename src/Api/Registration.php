<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\DomainRegistration;
use Porkbun\Internal\ClientContext;

final class Registration extends AbstractApi
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
    public function register(int $costInCents): DomainRegistration
    {
        $response = $this->post("/domain/create/{$this->domain}", [
            'cost' => $costInCents,
            'agreeToTerms' => 'yes',
        ]);

        return DomainRegistration::fromArray($response);
    }
}
