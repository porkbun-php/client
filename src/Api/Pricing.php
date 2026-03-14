<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\PricingCollection;

final class Pricing extends AbstractApi
{
    public function all(): PricingCollection
    {
        $data = $this->post('/pricing/get');

        /** @var array<string, array<string, mixed>> $pricingData */
        $pricingData = $data['pricing'] ?? [];

        return PricingCollection::fromArray($pricingData);
    }
}
