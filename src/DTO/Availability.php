<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class Availability implements JsonSerializable
{
    public ?float $effectivePrice;

    public ?int $priceInCents;

    public bool $hasPromoPrice;

    public bool $hasRenewalPromo;

    public bool $hasTransferPromo;

    public ?float $promoSavings;

    public bool $hasRateLimitInfo;

    public ?int $remainingChecks;

    public ?float $rateLimitUsagePercentage;

    public bool $isRateLimitNearExhausted;

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
        $this->effectivePrice = $this->price ?? $this->regularPrice;

        $this->priceInCents = $this->effectivePrice !== null
            ? (int) round($this->effectivePrice * 100)
            : null;

        $this->hasPromoPrice = $this->price !== null
            && $this->regularPrice !== null
            && $this->price < $this->regularPrice;

        $this->hasRenewalPromo = $this->renewalPrice !== null
            && $this->renewalRegularPrice !== null
            && $this->renewalPrice < $this->renewalRegularPrice;

        $this->hasTransferPromo = $this->transferPrice !== null
            && $this->transferRegularPrice !== null
            && $this->transferPrice < $this->transferRegularPrice;

        $this->promoSavings = ($this->price !== null && $this->regularPrice !== null && $this->price < $this->regularPrice)
            ? $this->regularPrice - $this->price
            : null;

        $this->hasRateLimitInfo = $this->limitTotal !== null && $this->limitUsed !== null;

        $this->remainingChecks = ($this->limitTotal !== null && $this->limitUsed !== null)
            ? max(0, $this->limitTotal - $this->limitUsed)
            : null;

        $this->rateLimitUsagePercentage = ($this->limitTotal !== null && $this->limitTotal !== 0 && $this->limitUsed !== null)
            ? ($this->limitUsed / $this->limitTotal) * 100
            : null;

        $this->isRateLimitNearExhausted = $this->rateLimitUsagePercentage !== null && $this->rateLimitUsagePercentage > 80;
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
            price: isset($response['price']) ? self::parsePrice($response['price']) : null,
            regularPrice: isset($response['regularPrice']) ? self::parsePrice($response['regularPrice']) : null,
            hasFirstYearPromo: ($response['firstYearPromo'] ?? 'no') === 'yes',
            isPremium: ($response['premium'] ?? 'no') === 'yes',
            minDuration: (int) ($response['minDuration'] ?? 1),
            renewalPrice: isset($renewalData['price']) ? self::parsePrice($renewalData['price']) : null,
            renewalRegularPrice: isset($renewalData['regularPrice']) ? self::parsePrice($renewalData['regularPrice']) : null,
            transferPrice: isset($transferData['price']) ? self::parsePrice($transferData['price']) : null,
            transferRegularPrice: isset($transferData['regularPrice']) ? self::parsePrice($transferData['regularPrice']) : null,
            limitTotal: isset($limits['limit']) ? (int) $limits['limit'] : null,
            limitUsed: isset($limits['used']) ? (int) $limits['used'] : null,
            limitTtl: isset($limits['TTL']) ? (int) $limits['TTL'] : null,
            limitNaturalLanguage: isset($limits['naturalLanguage']) ? (string) $limits['naturalLanguage'] : null,
        );
    }

    public function isAffordable(float $maxBudget): bool
    {
        if (!$this->isAvailable) {
            return false;
        }

        return $this->effectivePrice !== null && $this->effectivePrice <= $maxBudget;
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

        if ($this->hasRateLimitInfo) {
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

    private static function parsePrice(string|int|float $value): float
    {
        return (float) str_replace(',', '', (string) $value);
    }
}
