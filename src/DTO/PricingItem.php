<?php

declare(strict_types=1);

namespace Porkbun\DTO;

final readonly class PricingItem
{
    public function __construct(
        public string $tld,
        public float $registrationPrice,
        public float $renewalPrice,
        public ?float $transferPrice = null,
        /** @var array<mixed> */
        public array $coupons = [],
        public ?string $specialType = null,
    ) {
    }

    public static function fromArray(string $tld, array $data): self
    {
        return new self(
            tld: $tld,
            registrationPrice: (float) ($data['registration'] ?? 0),
            renewalPrice: (float) ($data['renewal'] ?? 0),
            transferPrice: isset($data['transfer']) ? (float) $data['transfer'] : null,
            coupons: $data['coupons'] ?? [],
            specialType: $data['specialType'] ?? null,
        );
    }

    public function isHandshake(): bool
    {
        return $this->specialType === 'handshake';
    }

    public function hasCoupons(): bool
    {
        return $this->coupons !== [];
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
}
