<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;
use Porkbun\Internal\TypeCaster;

final class AvailabilityResult implements JsonSerializable
{
    public ?float $effectivePrice { get => $this->price ?? $this->regularPrice; }

    public ?int $priceInCents {
        get => $this->effectivePrice !== null
            ? (int) round($this->effectivePrice * 100)
            : null;
    }

    public bool $hasPromoPrice {
        get => $this->price !== null
            && $this->regularPrice !== null
            && $this->price < $this->regularPrice;
    }

    public bool $hasRenewalPromo {
        get => $this->renewalPrice !== null
            && $this->renewalRegularPrice !== null
            && $this->renewalPrice < $this->renewalRegularPrice;
    }

    public bool $hasTransferPromo {
        get => $this->transferPrice !== null
            && $this->transferRegularPrice !== null
            && $this->transferPrice < $this->transferRegularPrice;
    }

    public ?float $promoSavings {
        get => ($this->price !== null && $this->regularPrice !== null && $this->price < $this->regularPrice)
            ? $this->regularPrice - $this->price
            : null;
    }

    public bool $hasRateLimitInfo { get => $this->limitTotal !== null && $this->limitUsed !== null; }

    public ?int $remainingChecks {
        get => ($this->limitTotal !== null && $this->limitUsed !== null)
            ? max(0, $this->limitTotal - $this->limitUsed)
            : null;
    }

    public function __construct(
        public readonly bool $isAvailable,
        public readonly string $type,
        public readonly ?float $price = null,
        public readonly ?float $regularPrice = null,
        public readonly bool $hasFirstYearPromo = false,
        public readonly bool $isPremium = false,
        public readonly int $minDuration = 1,
        public readonly ?float $renewalPrice = null,
        public readonly ?float $renewalRegularPrice = null,
        public readonly ?float $transferPrice = null,
        public readonly ?float $transferRegularPrice = null,
        public readonly ?int $limitTotal = null,
        public readonly ?int $limitUsed = null,
        public readonly ?int $limitTtl = null,
        public readonly ?string $limitNaturalLanguage = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        // Flat format detection: 'isAvailable' only exists at top level in our canonical
        // toArray() output. Porkbun's wire format nests it under 'response', so this is safe.
        if (array_key_exists('isAvailable', $data)) {
            return new self(
                isAvailable: TypeCaster::toBool($data['isAvailable']),
                type: (string) ($data['type'] ?? ''),
                price: $data['price'] ?? null,
                regularPrice: $data['regularPrice'] ?? null,
                hasFirstYearPromo: TypeCaster::toBool($data['hasFirstYearPromo'] ?? false),
                isPremium: TypeCaster::toBool($data['isPremium'] ?? false),
                minDuration: (int) ($data['minDuration'] ?? 1),
                renewalPrice: $data['renewalPrice'] ?? null,
                renewalRegularPrice: $data['renewalRegularPrice'] ?? null,
                transferPrice: $data['transferPrice'] ?? null,
                transferRegularPrice: $data['transferRegularPrice'] ?? null,
                limitTotal: $data['limitTotal'] ?? null,
                limitUsed: $data['limitUsed'] ?? null,
                limitTtl: $data['limitTtl'] ?? null,
                limitNaturalLanguage: $data['limitNaturalLanguage'] ?? null,
            );
        }

        // Wire format (from Porkbun API)
        $response = $data['response'] ?? [];
        $limits = $data['limits'] ?? [];
        $additional = $response['additional'] ?? [];
        $renewalData = $additional['renewal'] ?? [];
        $transferData = $additional['transfer'] ?? [];

        return new self(
            isAvailable: TypeCaster::toBool($response['isAvailable'] ?? $response['avail'] ?? false),
            type: (string) ($response['type'] ?? ''),
            price: isset($response['price']) ? TypeCaster::toPrice($response['price']) : null,
            regularPrice: isset($response['regularPrice']) ? TypeCaster::toPrice($response['regularPrice']) : null,
            hasFirstYearPromo: TypeCaster::toBool($response['hasFirstYearPromo'] ?? $response['firstYearPromo'] ?? false),
            isPremium: TypeCaster::toBool($response['isPremium'] ?? $response['premium'] ?? false),
            minDuration: (int) ($response['minDuration'] ?? 1),
            renewalPrice: isset($renewalData['price']) ? TypeCaster::toPrice($renewalData['price']) : null,
            renewalRegularPrice: isset($renewalData['regularPrice']) ? TypeCaster::toPrice($renewalData['regularPrice']) : null,
            transferPrice: isset($transferData['price']) ? TypeCaster::toPrice($transferData['price']) : null,
            transferRegularPrice: isset($transferData['regularPrice']) ? TypeCaster::toPrice($transferData['regularPrice']) : null,
            limitTotal: isset($limits['total']) ? (int) $limits['total'] : (isset($limits['limit']) ? (int) $limits['limit'] : null),
            limitUsed: isset($limits['used']) ? (int) $limits['used'] : null,
            limitTtl: isset($limits['ttl']) ? (int) $limits['ttl'] : (isset($limits['TTL']) ? (int) $limits['TTL'] : null),
            limitNaturalLanguage: isset($limits['naturalLanguage']) ? (string) $limits['naturalLanguage'] : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'isAvailable' => $this->isAvailable,
            'type' => $this->type,
            'minDuration' => $this->minDuration,
            'hasFirstYearPromo' => $this->hasFirstYearPromo,
            'isPremium' => $this->isPremium,
        ];

        if ($this->price !== null) {
            $data['price'] = $this->price;
        }
        if ($this->regularPrice !== null) {
            $data['regularPrice'] = $this->regularPrice;
        }
        if ($this->renewalPrice !== null) {
            $data['renewalPrice'] = $this->renewalPrice;
        }
        if ($this->renewalRegularPrice !== null) {
            $data['renewalRegularPrice'] = $this->renewalRegularPrice;
        }
        if ($this->transferPrice !== null) {
            $data['transferPrice'] = $this->transferPrice;
        }
        if ($this->transferRegularPrice !== null) {
            $data['transferRegularPrice'] = $this->transferRegularPrice;
        }
        if ($this->limitTotal !== null) {
            $data['limitTotal'] = $this->limitTotal;
        }
        if ($this->limitUsed !== null) {
            $data['limitUsed'] = $this->limitUsed;
        }
        if ($this->limitTtl !== null) {
            $data['limitTtl'] = $this->limitTtl;
        }
        if ($this->limitNaturalLanguage !== null) {
            $data['limitNaturalLanguage'] = $this->limitNaturalLanguage;
        }

        return $data;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
