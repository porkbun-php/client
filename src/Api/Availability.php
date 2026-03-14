<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\AvailabilityResult;
use Porkbun\Internal\ClientContext;

final class Availability extends AbstractApi
{
    public function __construct(
        ClientContext $clientContext,
        private readonly string $domain,
    ) {
        parent::__construct($clientContext);
    }

    public function check(): AvailabilityResult
    {
        $response = $this->post("/domain/checkDomain/{$this->domain}");

        return AvailabilityResult::fromArray($response);
    }
}
