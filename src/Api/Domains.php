<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Generator;
use Porkbun\DTO\Domain;
use Porkbun\DTO\DomainCollection;
use Porkbun\Exception\InvalidArgumentException;

final class Domains extends AbstractApi
{
    private const int PAGE_SIZE = 1000;

    public function all(int $start = 0, bool $includeLabels = false): DomainCollection
    {
        $data = [
            'start' => $start,
            'includeLabels' => $includeLabels ? 'yes' : 'no',
        ];

        $response = $this->post('/domain/listAll', $data);

        /** @var array<array<string, mixed>> $responseDomains */
        $responseDomains = $response['domains'] ?? [];

        return DomainCollection::fromArray($responseDomains);
    }

    /** @return Generator<int, Domain> */
    public function allPages(bool $includeLabels = false): Generator
    {
        $start = 0;

        do {
            $collection = $this->all($start, $includeLabels);

            foreach ($collection as $domain) {
                yield $domain;
            }

            $pageCount = count($collection);
            $start += self::PAGE_SIZE;
        } while ($pageCount >= self::PAGE_SIZE);
    }

    /** @return Generator<int, DomainCollection> */
    public function allCollections(bool $includeLabels = false): Generator
    {
        $start = 0;

        do {
            $collection = $this->all($start, $includeLabels);

            if ($collection->isNotEmpty()) {
                yield $collection;
            }

            $pageCount = count($collection);
            $start += self::PAGE_SIZE;
        } while ($pageCount >= self::PAGE_SIZE);
    }

    /** @return array<string, array{status: string, message?: string}> */
    public function enableAutoRenew(string ...$domains): array
    {
        return $this->updateAutoRenew(true, ...$domains);
    }

    /** @return array<string, array{status: string, message?: string}> */
    public function disableAutoRenew(string ...$domains): array
    {
        return $this->updateAutoRenew(false, ...$domains);
    }

    /** @return array<string, array{status: string, message?: string}> */
    private function updateAutoRenew(bool $enable, string ...$domains): array
    {
        if ($domains === []) {
            throw new InvalidArgumentException('At least one domain must be provided');
        }

        $endpoint = '/domain/updateAutoRenew';
        $data = ['status' => $enable ? 'on' : 'off'];

        if (count($domains) === 1) {
            $endpoint .= '/' . $domains[0];
        } elseif (count($domains) > 1) {
            $data['domains'] = $domains;
        }

        $response = $this->post($endpoint, $data);

        return $response['results'] ?? [];
    }
}
