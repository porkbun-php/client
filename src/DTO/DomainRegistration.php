<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class DomainRegistration implements JsonSerializable
{
    public function __construct(
        public string $domain,
        public int $cost,
        public int $orderId,
        public int $balance,
        /** @var ?array{attempts?: array{limit: int, used: int}, success?: array{limit: int, used: int}} */
        public ?array $limits = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            domain: (string) ($data['domain'] ?? ''),
            cost: (int) ($data['cost'] ?? 0),
            orderId: (int) ($data['orderId'] ?? 0),
            balance: (int) ($data['balance'] ?? 0),
            limits: isset($data['limits']) && is_array($data['limits']) ? $data['limits'] : null,
        );
    }

    /** Cost in dollars (e.g., 1108 cents = $11.08) */
    public function getCostInDollars(): float
    {
        return $this->cost / 100;
    }

    /** Balance in dollars */
    public function getBalanceInDollars(): float
    {
        return $this->balance / 100;
    }

    public function hasLimits(): bool
    {
        return $this->limits !== null && $this->limits !== [];
    }

    public function getAttemptsLimit(): ?array
    {
        return $this->limits['attempts'] ?? null;
    }

    public function getSuccessLimit(): ?array
    {
        return $this->limits['success'] ?? null;
    }

    public function getRemainingAttempts(): ?int
    {
        $attempts = $this->getAttemptsLimit();
        if ($attempts === null) {
            return null;
        }

        $limit = (int) ($attempts['limit'] ?? 0);
        $used = (int) ($attempts['used'] ?? 0);

        return max(0, $limit - $used);
    }

    public function getRemainingSuccessfulRegistrations(): ?int
    {
        $success = $this->getSuccessLimit();
        if ($success === null) {
            return null;
        }

        $limit = (int) ($success['limit'] ?? 0);
        $used = (int) ($success['used'] ?? 0);

        return max(0, $limit - $used);
    }

    public function toArray(): array
    {
        $data = [
            'domain' => $this->domain,
            'cost' => $this->cost,
            'orderId' => $this->orderId,
            'balance' => $this->balance,
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
