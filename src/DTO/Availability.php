<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class Availability implements JsonSerializable
{
    public function __construct(
        public bool $isAvailable,
        public string $type,
        public ?float $price = null,
        public ?float $regularPrice = null,
        public bool $hasFirstYearPromo = false,
        public bool $isPremium = false,
        public int $minDuration = 1,
        public ?float $renewalPrice = null,
        public ?float $renewalRegularPrice = null,
        public ?float $transferPrice = null,
        public ?float $transferRegularPrice = null,
        public ?int $limitTotal = null,
        public ?int $limitUsed = null,
        public ?int $limitTtl = null,
        public ?string $limitNaturalLanguage = null,
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
            minDuration: (int) ($response['minDuration'] ?? 1),
            renewalPrice: isset($renewalData['price']) ? (float) $renewalData['price'] : null,
            renewalRegularPrice: isset($renewalData['regularPrice']) ? (float) $renewalData['regularPrice'] : null,
            transferPrice: isset($transferData['price']) ? (float) $transferData['price'] : null,
            transferRegularPrice: isset($transferData['regularPrice']) ? (float) $transferData['regularPrice'] : null,
            limitTotal: isset($limits['limit']) ? (int) $limits['limit'] : null,
            limitUsed: isset($limits['used']) ? (int) $limits['used'] : null,
            limitTtl: isset($limits['TTL']) ? (int) $limits['TTL'] : null,
            limitNaturalLanguage: isset($limits['naturalLanguage']) ? (string) $limits['naturalLanguage'] : null,
        );
    }

    public function hasPromoPrice(): bool
    {
        return $this->price !== null
            && $this->regularPrice !== null
            && $this->price < $this->regularPrice;
    }

    public function hasRenewalPromo(): bool
    {
        return $this->renewalPrice !== null
            && $this->renewalRegularPrice !== null
            && $this->renewalPrice < $this->renewalRegularPrice;
    }

    public function hasTransferPromo(): bool
    {
        return $this->transferPrice !== null
            && $this->transferRegularPrice !== null
            && $this->transferPrice < $this->transferRegularPrice;
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
                'minDuration' => $this->minDuration,
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
                $data['response']['additional']['renewal'] = [
                    'type' => 'renewal',
                    'price' => $this->renewalPrice,
                ];
                if ($this->renewalRegularPrice !== null) {
                    $data['response']['additional']['renewal']['regularPrice'] = $this->renewalRegularPrice;
                }
            }

            if ($this->transferPrice !== null) {
                $data['response']['additional']['transfer'] = [
                    'type' => 'transfer',
                    'price' => $this->transferPrice,
                ];
                if ($this->transferRegularPrice !== null) {
                    $data['response']['additional']['transfer']['regularPrice'] = $this->transferRegularPrice;
                }
            }
        }

        if ($this->hasRateLimitInfo()) {
            $data['limits'] = [
                'limit' => $this->limitTotal,
                'used' => $this->limitUsed,
            ];
            if ($this->limitTtl !== null) {
                $data['limits']['TTL'] = $this->limitTtl;
            }
            if ($this->limitNaturalLanguage !== null) {
                $data['limits']['naturalLanguage'] = $this->limitNaturalLanguage;
            }
        }

        return $data;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
