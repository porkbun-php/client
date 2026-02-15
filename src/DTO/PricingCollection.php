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
 * @implements IteratorAggregate<string, PricingItem>
 */
final class PricingCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param array<string, PricingItem> $items
     */
    public function __construct(private array $items = [])
    {
    }

    /** @param array<string, array<string, mixed>> $pricingData */
    public static function fromArray(array $pricingData): self
    {
        $items = [];

        foreach ($pricingData as $tld => $data) {
            /** @var array<string, mixed> $data */
            $tldString = (string) $tld;
            $items[$tldString] = PricingItem::fromArray($tldString, $data);
        }

        return new self($items);
    }

    /** @return array<string, PricingItem> */
    public function all(): array
    {
        return $this->items;
    }

    /** @return array<PricingItem> */
    public function items(): array
    {
        return array_values($this->items);
    }

    public function get(string $tld): ?PricingItem
    {
        return $this->items[$tld] ?? null;
    }

    public function has(string $tld): bool
    {
        return isset($this->items[$tld]);
    }

    /** @return array<string> */
    public function tlds(): array
    {
        return array_keys($this->items);
    }

    /** @return array<PricingItem> */
    public function cheapest(int $limit = 10): array
    {
        $items = $this->items();
        usort($items, fn (PricingItem $a, PricingItem $b): int => $a->registrationPrice <=> $b->registrationPrice);

        return array_slice($items, 0, $limit);
    }

    public function first(): ?PricingItem
    {
        return $this->items()[0] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    #[Override]
    public function count(): int
    {
        return count($this->items);
    }

    #[Override]
    public function jsonSerialize(): array
    {
        $result = [];
        foreach ($this->items as $tld => $item) {
            $result[$tld] = $item->toArray();
        }

        return $result;
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
