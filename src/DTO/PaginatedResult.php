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
final readonly class PaginatedResult implements Countable, IteratorAggregate, JsonSerializable
{
    public bool $hasMore;

    public ?int $nextStart;

    public function __construct(
        private DomainCollection $collection,
        public int $start,
        int $pageSize,
    ) {
        $this->hasMore = count($collection) >= $pageSize;
        $this->nextStart = $this->hasMore ? $start + $pageSize : null;
    }

    public function domains(): DomainCollection
    {
        return $this->collection;
    }

    /** @return list<Domain> */
    public function items(): array
    {
        return $this->collection->items();
    }

    public function toArray(): array
    {
        return [
            'domains' => $this->collection->toArray(),
            'start' => $this->start,
            'hasMore' => $this->hasMore,
            'nextStart' => $this->nextStart,
        ];
    }

    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->collection->items());
    }

    #[Override]
    public function count(): int
    {
        return count($this->collection);
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
