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
 * @implements IteratorAggregate<int, UrlForward>
 */
final class UrlForwardCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var list<UrlForward> */
    private array $forwards;

    /** @param array<UrlForward> $forwards */
    public function __construct(array $forwards = [])
    {
        $this->forwards = array_values($forwards);
    }

    /** @param array<array<string, mixed>> $forwardsData */
    public static function fromArray(array $forwardsData): self
    {
        return new self(array_map(UrlForward::fromArray(...), $forwardsData));
    }

    /** @return list<UrlForward> */
    public function items(): array
    {
        return $this->forwards;
    }

    public function first(): ?UrlForward
    {
        return $this->forwards[0] ?? null;
    }

    public function last(): ?UrlForward
    {
        return $this->forwards !== [] ? $this->forwards[array_key_last($this->forwards)] : null;
    }

    public function find(int $id): ?UrlForward
    {
        return array_find($this->forwards, fn (UrlForward $urlForward): bool => $urlForward->id === $id);
    }

    public function has(int $id): bool
    {
        return $this->find($id) !== null;
    }

    public function filter(callable $callback): self
    {
        return new self(array_values(array_filter($this->forwards, $callback)));
    }

    public function isEmpty(): bool
    {
        return $this->forwards === [];
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->forwards);
    }

    #[Override]
    public function count(): int
    {
        return count($this->forwards);
    }

    public function toArray(): array
    {
        return array_map(fn (UrlForward $urlForward): array => $urlForward->toArray(), $this->forwards);
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
