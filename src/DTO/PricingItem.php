<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class PricingItem implements JsonSerializable
{
    public bool $isHandshake;

    public bool $hasCoupons;

    public function __construct(
        public string $tld,
        public float $registrationPrice,
        public float $renewalPrice,
        public ?float $transferPrice = null,
        /** @var array<mixed> */
        public array $coupons = [],
        public ?string $specialType = null,
    ) {
        $this->isHandshake = $this->specialType === 'handshake';
        $this->hasCoupons = $this->coupons !== [];
    }

    public static function fromArray(string $tld, array $data): self
    {
        return new self(
            tld: $tld,
            registrationPrice: self::parsePrice($data['registration'] ?? '0'),
            renewalPrice: self::parsePrice($data['renewal'] ?? '0'),
            transferPrice: isset($data['transfer']) ? self::parsePrice($data['transfer']) : null,
            coupons: $data['coupons'] ?? [],
            specialType: $data['specialType'] ?? null,
        );
    }

    public function toArray(): array
    {
        $result = [
            'tld' => $this->tld,
            'registration' => (string) $this->registrationPrice,
            'renewal' => (string) $this->renewalPrice,
        ];

        if ($this->transferPrice !== null) {
            $result['transfer'] = (string) $this->transferPrice;
        }

        if ($this->coupons !== []) {
            $result['coupons'] = $this->coupons;
        }

        if ($this->specialType !== null) {
            $result['specialType'] = $this->specialType;
        }

        return $result;
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
