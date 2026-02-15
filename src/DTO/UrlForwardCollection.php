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
        $forwards = [];
        foreach ($forwardsData as $forwardData) {
            $forwards[] = UrlForward::fromArray($forwardData);
        }

        return new self($forwards);
    }

    /** @return list<UrlForward> */
    public function all(): array
    {
        return $this->forwards;
    }

    public function first(): ?UrlForward
    {
        return $this->forwards[0] ?? null;
    }

    public function last(): ?UrlForward
    {
        if ($this->forwards === []) {
            return null;
        }

        return $this->forwards[count($this->forwards) - 1];
    }

    /** @return list<UrlForward> */
    public function filter(callable $callback): array
    {
        return array_values(array_filter($this->forwards, $callback));
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

    #[Override]
    public function jsonSerialize(): array
    {
        return array_map(fn (UrlForward $urlForward): array => $urlForward->toArray(), $this->forwards);
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
