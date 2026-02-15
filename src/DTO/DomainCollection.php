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
        $domains = [];
        foreach ($domainsData as $domainData) {
            $domains[] = Domain::fromArray($domainData);
        }

        return new self($domains);
    }

    /** @return list<Domain> */
    public function all(): array
    {
        return $this->domains;
    }

    public function first(): ?Domain
    {
        return $this->domains[0] ?? null;
    }

    public function last(): ?Domain
    {
        if ($this->domains === []) {
            return null;
        }

        return $this->domains[count($this->domains) - 1];
    }

    public function get(string $domainName): ?Domain
    {
        foreach ($this->domains as $domain) {
            if ($domain->domain === $domainName) {
                return $domain;
            }
        }

        return null;
    }

    public function has(string $domainName): bool
    {
        return $this->get($domainName) instanceof Domain;
    }

    /** @return list<Domain> */
    public function getExpiringSoon(int $daysThreshold = 30): array
    {
        return array_values(array_filter(
            $this->domains,
            fn (Domain $domain): bool => $domain->isExpiringSoon($daysThreshold)
        ));
    }

    /** @return list<Domain> */
    public function filter(callable $callback): array
    {
        return array_values(array_filter($this->domains, $callback));
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

    #[Override]
    public function jsonSerialize(): array
    {
        return array_map(fn (Domain $domain): array => $domain->toArray(), $this->domains);
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
