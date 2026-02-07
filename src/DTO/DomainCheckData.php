<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class DomainCheckData implements JsonSerializable
{
    public function __construct(
        public bool $isAvailable,
        public string $type,
        public ?float $price = null,
        public ?float $regularPrice = null,
        public bool $hasFirstYearPromo = false,
        public bool $isPremium = false,
        public ?float $renewalPrice = null,
        public ?float $transferPrice = null,
        public ?int $limitTotal = null,
        public ?int $limitUsed = null,
        public ?array $additionalInfo = null,
        public ?string $registrar = null,
        public ?string $expirationDate = null,
        public ?array $whoisInfo = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $response = $data['response'] ?? [];
        $limits = $data['limits'] ?? [];
        $additional = $response['additional'] ?? [];
        $renewalData = $additional['renewal'] ?? [];
        $transferData = $additional['transfer'] ?? [];

        return new self(
            isAvailable: ($response['avail'] ?? 'no') === 'yes',
            type: (string) ($response['type'] ?? ''),
            price: isset($response['price']) ? (float) $response['price'] : null,
            regularPrice: isset($response['regularPrice']) ? (float) $response['regularPrice'] : null,
            hasFirstYearPromo: ($response['firstYearPromo'] ?? 'no') === 'yes',
            isPremium: ($response['premium'] ?? 'no') === 'yes',
            renewalPrice: isset($renewalData['price']) ? (float) $renewalData['price'] : null,
            transferPrice: isset($transferData['price']) ? (float) $transferData['price'] : null,
            limitTotal: isset($limits['limit']) ? (int) $limits['limit'] : null,
            limitUsed: isset($limits['used']) ? (int) $limits['used'] : null,
            additionalInfo: is_array($data['additionalInfo'] ?? null) ? $data['additionalInfo'] : null,
            registrar: $data['registrar'] ?? null,
            expirationDate: $data['expirationDate'] ?? null,
            whoisInfo: is_array($data['whoisInfo'] ?? null) ? $data['whoisInfo'] : null,
        );
    }

    public function hasPromoPrice(): bool
    {
        return $this->price !== null
            && $this->regularPrice !== null
            && $this->price < $this->regularPrice;
    }

    public function getPromoSavings(): ?float
    {
        if ($this->price === null || $this->regularPrice === null || $this->price >= $this->regularPrice) {
            return null;
        }

        return $this->regularPrice - $this->price;
    }

    public function getEffectivePrice(): ?float
    {
        return $this->price ?? $this->regularPrice;
    }

    public function hasRateLimitInfo(): bool
    {
        return $this->limitTotal !== null && $this->limitUsed !== null;
    }

    public function getRemainingChecks(): ?int
    {
        if ($this->limitTotal === null || $this->limitUsed === null) {
            return null;
        }

        return max(0, $this->limitTotal - $this->limitUsed);
    }

    public function getRateLimitUsagePercentage(): ?float
    {
        if ($this->limitTotal === null || $this->limitTotal === 0 || $this->limitUsed === null) {
            return null;
        }

        return ($this->limitUsed / $this->limitTotal) * 100;
    }

    public function isRateLimitNearExhausted(): bool
    {
        $percentage = $this->getRateLimitUsagePercentage();

        return $percentage !== null && $percentage > 80;
    }

    public function isAvailableAndAffordable(float $maxBudget): bool
    {
        if (!$this->isAvailable) {
            return false;
        }

        $price = $this->getEffectivePrice();

        return $price !== null && $price <= $maxBudget;
    }

    public function toArray(): array
    {
        $data = [
            'response' => [
                'avail' => $this->isAvailable ? 'yes' : 'no',
                'type' => $this->type,
            ],
        ];

        if ($this->price !== null) {
            $data['response']['price'] = $this->price;
        }
        if ($this->regularPrice !== null) {
            $data['response']['regularPrice'] = $this->regularPrice;
        }
        if ($this->hasFirstYearPromo) {
            $data['response']['firstYearPromo'] = 'yes';
        }
        if ($this->isPremium) {
            $data['response']['premium'] = 'yes';
        }

        if ($this->renewalPrice !== null || $this->transferPrice !== null) {
            $data['response']['additional'] = [];
            if ($this->renewalPrice !== null) {
                $data['response']['additional']['renewal'] = ['price' => $this->renewalPrice];
            }
            if ($this->transferPrice !== null) {
                $data['response']['additional']['transfer'] = ['price' => $this->transferPrice];
            }
        }

        if ($this->hasRateLimitInfo()) {
            $data['limits'] = [
                'limit' => $this->limitTotal,
                'used' => $this->limitUsed,
            ];
        }

        if ($this->additionalInfo !== null) {
            $data['additionalInfo'] = $this->additionalInfo;
        }
        if ($this->registrar !== null) {
            $data['registrar'] = $this->registrar;
        }
        if ($this->expirationDate !== null) {
            $data['expirationDate'] = $this->expirationDate;
        }
        if ($this->whoisInfo !== null) {
            $data['whoisInfo'] = $this->whoisInfo;
        }

        return $data;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
