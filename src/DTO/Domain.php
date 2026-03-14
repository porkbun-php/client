<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use DateTimeImmutable;
use Exception;
use JsonSerializable;
use Override;
use Porkbun\Internal\TypeCaster;

final readonly class Domain implements JsonSerializable
{
    public string $tld;

    public function __construct(
        public string $domain,
        public string $status,
        ?string $tld = null,
        public ?DateTimeImmutable $createDate = null,
        public ?DateTimeImmutable $expireDate = null,
        public ?bool $securityLock = null,
        public ?bool $whoisPrivacy = null,
        public ?bool $autoRenew = null,
        public ?bool $isExternal = null,
        /** @var list<DomainLabel>|null */
        public ?array $labels = null,
    ) {
        if ($tld !== null) {
            $this->tld = $tld;
        } else {
            $parts = explode('.', $this->domain);
            $this->tld = end($parts);
        }
    }

    public static function fromArray(array $data): self
    {
        $createDate = null;
        if (isset($data['createDate']) && $data['createDate'] !== '') {
            try {
                $createDate = new DateTimeImmutable((string) $data['createDate']);
            } catch (Exception) {
                // Invalid date format - leave as null
            }
        }

        $expireDate = null;
        if (isset($data['expireDate']) && $data['expireDate'] !== '') {
            try {
                $expireDate = new DateTimeImmutable((string) $data['expireDate']);
            } catch (Exception) {
                // Invalid date format - leave as null
            }
        }

        $labels = null;
        if (isset($data['labels']) && is_array($data['labels'])) {
            $labels = array_values(array_map(
                DomainLabel::fromArray(...),
                array_filter($data['labels'], is_array(...)),
            ));
        }

        return new self(
            domain: (string) ($data['domain'] ?? ''),
            status: (string) ($data['status'] ?? 'ACTIVE'),
            tld: $data['tld'] ?? null,
            createDate: $createDate,
            expireDate: $expireDate,
            securityLock: isset($data['securityLock']) ? TypeCaster::toBool($data['securityLock']) : null,
            whoisPrivacy: isset($data['whoisPrivacy']) ? TypeCaster::toBool($data['whoisPrivacy']) : null,
            autoRenew: isset($data['autoRenew']) ? TypeCaster::toBool($data['autoRenew']) : null,
            isExternal: isset($data['notLocal']) || isset($data['isExternal'])
                ? TypeCaster::toBool($data['notLocal'] ?? $data['isExternal'])
                : null,
            labels: $labels,
        );
    }

    public function toArray(): array
    {
        $result = [
            'domain' => $this->domain,
            'status' => $this->status,
            'tld' => $this->tld,
        ];

        if ($this->createDate instanceof DateTimeImmutable) {
            $result['createDate'] = $this->createDate->format('c');
        }

        if ($this->expireDate instanceof DateTimeImmutable) {
            $result['expireDate'] = $this->expireDate->format('c');
        }

        if ($this->securityLock !== null) {
            $result['securityLock'] = $this->securityLock;
        }

        if ($this->whoisPrivacy !== null) {
            $result['whoisPrivacy'] = $this->whoisPrivacy;
        }

        if ($this->autoRenew !== null) {
            $result['autoRenew'] = $this->autoRenew;
        }

        if ($this->isExternal !== null) {
            $result['isExternal'] = $this->isExternal;
        }

        if ($this->labels !== null) {
            $result['labels'] = array_map(
                fn (DomainLabel $domainLabel): array => $domainLabel->toArray(),
                $this->labels
            );
        }

        return $result;
    }

    public function isExpiringSoon(int $daysThreshold = 30): bool
    {
        if (!$this->expireDate instanceof DateTimeImmutable) {
            return false;
        }

        $now = new DateTimeImmutable();
        $diff = $now->diff($this->expireDate);

        return $diff->days !== false && $diff->days <= $daysThreshold && $diff->invert === 0;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
