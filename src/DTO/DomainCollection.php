<?php

declare(strict_types=1);

namespace Porkbun\DTO;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Override;
use Traversable;

/**
 * @implements IteratorAggregate<int, Domain>
 */
final class DomainCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var list<Domain> */
    private array $domains;

    /** @param array<Domain> $domains */
    public function __construct(array $domains = [])
    {
        $this->domains = array_values($domains);
    }

    /** @param array<array<string, mixed>> $domainsData */
    public static function fromArray(array $domainsData): self
    {
        return new self(array_map(Domain::fromArray(...), $domainsData));
    }

    /** @return list<Domain> */
    public function items(): array
    {
        return $this->domains;
    }

    public function first(): ?Domain
    {
        return $this->domains[0] ?? null;
    }

    public function last(): ?Domain
    {
        return $this->domains !== [] ? $this->domains[array_key_last($this->domains)] : null;
    }

    public function find(string $domainName): ?Domain
    {
        return array_find($this->domains, fn (Domain $domain): bool => strcasecmp($domain->domain, $domainName) === 0);
    }

    public function has(string $domainName): bool
    {
        return $this->find($domainName) !== null;
    }

    public function byTld(string $tld): self
    {
        return new self(array_values(array_filter(
            $this->domains,
            fn (Domain $domain): bool => strcasecmp($domain->tld, $tld) === 0
        )));
    }

    public function byStatus(string $status): self
    {
        return new self(array_values(array_filter(
            $this->domains,
            fn (Domain $domain): bool => strcasecmp($domain->status, $status) === 0
        )));
    }

    public function expiringSoon(int $daysThreshold = 30): self
    {
        return new self(array_values(array_filter(
            $this->domains,
            fn (Domain $domain): bool => $domain->isExpiringSoon($daysThreshold)
        )));
    }

    public function filter(callable $callback): self
    {
        return new self(array_values(array_filter($this->domains, $callback)));
    }

    public function isEmpty(): bool
    {
        return $this->domains === [];
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->domains);
    }

    #[Override]
    public function count(): int
    {
        return count($this->domains);
    }

    public function toArray(): array
    {
        return array_map(fn (Domain $domain): array => $domain->toArray(), $this->domains);
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
