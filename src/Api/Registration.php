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
     * @param int $cost Expected cost in pennies (from availability check)
     */
    public function register(int $cost): DomainRegistration
    {
        $response = $this->post("/domain/create/{$this->domain}", [
            'cost' => $cost,
            'agreeToTerms' => 'yes',
        ]);

        return DomainRegistration::fromArray($response);
    }
}
