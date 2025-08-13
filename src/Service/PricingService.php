<?php

declare(strict_types=1);

namespace Porkbun\Service;

use Porkbun\Request\GetPricingRequest;
use Porkbun\Response\PricingResponse;

class PricingService extends AbstractService
{
    public function getPricing(): array
    {
        return $this->post('/pricing/get', []);
    }

    protected function requiresAuth(): bool
    {
        return false;
    }

    // Request/Response Pattern Methods

    public function getPricingAsResponse(): PricingResponse
    {
        $response = $this->getPricing();

        return new PricingResponse($response);
    }

    public function getPricingFromRequest(): PricingResponse
    {
        $getPricingRequest = new GetPricingRequest();
        $response = $this->sendRequest($getPricingRequest);

        return new PricingResponse($response);
    }
}
