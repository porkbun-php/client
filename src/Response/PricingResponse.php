<?php

declare(strict_types=1);

namespace Porkbun\Response;

class PricingResponse extends AbstractResponse
{
    public function getPricing(): array
    {
        return $this->rawData['pricing'] ?? [];
    }

    public function getDomainPrice(string $tld): ?array
    {
        return $this->rawData['pricing'][$tld] ?? null;
    }

    public function getRegistrationPrice(string $tld): string|null
    {
        return $this->rawData['pricing'][$tld]['registration'] ?? null;
    }

    public function getRenewalPrice(string $tld): string|null
    {
        return $this->rawData['pricing'][$tld]['renewal'] ?? null;
    }

    public function getRegistrationPriceAsFloat(string $tld): float|null
    {
        $price = $this->getRegistrationPrice($tld);

        return match (true) {
            $price === null => null,
            is_numeric($price) => (float) $price,
            default => null
        };
    }

    public function getRenewalPriceAsFloat(string $tld): float|null
    {
        $price = $this->getRenewalPrice($tld);

        return match (true) {
            $price === null => null,
            is_numeric($price) => (float) $price,
            default => null
        };
    }

    public function hasDomain(string $tld): bool
    {
        return isset($this->rawData['pricing'][$tld]);
    }

    public function getAllTlds(): array
    {
        return array_keys($this->rawData['pricing'] ?? []);
    }
}
