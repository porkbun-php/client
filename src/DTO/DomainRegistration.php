<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final class DomainRegistration implements JsonSerializable
{
    public float $costInDollars { get => $this->costInCents / 100; }

    public float $balanceInDollars { get => $this->balanceInCents / 100; }

    public bool $hasLimits { get => $this->limits !== null && $this->limits !== []; }

    public ?int $remainingAttempts {
        get {
            $limit = $this->limits['attempts'] ?? null;

            return $limit !== null
                ? max(0, (int) ($limit['limit'] ?? 0) - (int) ($limit['used'] ?? 0))
                : null;
        }
    }

    public ?int $remainingRegistrations {
        get {
            $limit = $this->limits['success'] ?? null;

            return $limit !== null
                ? max(0, (int) ($limit['limit'] ?? 0) - (int) ($limit['used'] ?? 0))
                : null;
        }
    }

    public function __construct(
        public readonly string $domain,
        public readonly int $costInCents,
        public readonly int $orderId,
        public readonly int $balanceInCents,
        /** @var ?array{attempts?: array{limit: int, used: int}, success?: array{limit: int, used: int}} */
        public readonly ?array $limits = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            domain: (string) ($data['domain'] ?? ''),
            costInCents: (int) ($data['costInCents'] ?? $data['cost'] ?? 0),
            orderId: (int) ($data['orderId'] ?? 0),
            balanceInCents: (int) ($data['balanceInCents'] ?? $data['balance'] ?? 0),
            limits: isset($data['limits']) && is_array($data['limits']) ? $data['limits'] : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'domain' => $this->domain,
            'costInCents' => $this->costInCents,
            'orderId' => $this->orderId,
            'balanceInCents' => $this->balanceInCents,
        ];

        if ($this->limits !== null) {
            $data['limits'] = $this->limits;
        }

        return $data;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
