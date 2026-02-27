<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use JsonSerializable;
use Override;

final readonly class DomainRegistration implements JsonSerializable
{
    public bool $hasLimits;

    public ?array $attemptsLimit;

    public ?array $successLimit;

    public ?int $remainingAttempts;

    public ?int $remainingRegistrations;

    public function __construct(
        public string $domain,
        public int $cost,
        public int $orderId,
        public int $balance,
        /** @var ?array{attempts?: array{limit: int, used: int}, success?: array{limit: int, used: int}} */
        public ?array $limits = null,
    ) {
        $this->hasLimits = $this->limits !== null && $this->limits !== [];
        $this->attemptsLimit = $this->limits['attempts'] ?? null;
        $this->successLimit = $this->limits['success'] ?? null;

        if ($this->attemptsLimit !== null) {
            $limit = (int) ($this->attemptsLimit['limit'] ?? 0);
            $used = (int) ($this->attemptsLimit['used'] ?? 0);
            $this->remainingAttempts = max(0, $limit - $used);
        } else {
            $this->remainingAttempts = null;
        }

        if ($this->successLimit !== null) {
            $limit = (int) ($this->successLimit['limit'] ?? 0);
            $used = (int) ($this->successLimit['used'] ?? 0);
            $this->remainingRegistrations = max(0, $limit - $used);
        } else {
            $this->remainingRegistrations = null;
        }
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
    public function costInDollars(): float
    {
        return $this->cost / 100;
    }

    /** Balance in dollars */
    public function balanceInDollars(): float
    {
        return $this->balance / 100;
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
