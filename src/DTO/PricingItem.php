<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;
use Porkbun\Internal\TypeCaster;

final class PricingItem implements JsonSerializable
{
    public int $registrationPriceInCents { get => (int) round($this->registrationPrice * 100); }

    public int $renewalPriceInCents { get => (int) round($this->renewalPrice * 100); }

    public bool $isHandshake { get => $this->specialType === 'handshake'; }

    public bool $hasCoupons { get => $this->coupons !== []; }

    public function __construct(
        public readonly string $tld,
        public readonly float $registrationPrice,
        public readonly float $renewalPrice,
        public readonly ?float $transferPrice = null,
        /** @var array<mixed> */
        public readonly array $coupons = [],
        public readonly ?string $specialType = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            tld: (string) ($data['tld'] ?? ''),
            registrationPrice: TypeCaster::toPrice($data['registrationPrice'] ?? $data['registration'] ?? '0'),
            renewalPrice: TypeCaster::toPrice($data['renewalPrice'] ?? $data['renewal'] ?? '0'),
            transferPrice: isset($data['transferPrice']) || isset($data['transfer'])
                ? TypeCaster::toPrice($data['transferPrice'] ?? $data['transfer'])
                : null,
            coupons: $data['coupons'] ?? [],
            specialType: $data['specialType'] ?? null,
        );
    }

    public function toArray(): array
    {
        $result = [
            'tld' => $this->tld,
            'registrationPrice' => $this->registrationPrice,
            'renewalPrice' => $this->renewalPrice,
        ];

        if ($this->transferPrice !== null) {
            $result['transferPrice'] = $this->transferPrice;
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
}
