<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Generator;
use Porkbun\DTO\AutoRenewResult;
use Porkbun\DTO\Domain;
use Porkbun\DTO\DomainCollection;
use Porkbun\DTO\PaginatedResult;

final class Domains extends AbstractApi
{
    private const int PAGE_SIZE = 1000;

    public function find(string $domain): ?Domain
    {
        foreach ($this->all() as $d) {
            if (strcasecmp($d->domain, $domain) === 0) {
                return $d;
            }
        }

        return null;
    }

    public function list(int $start = 0, bool $includeLabels = false): PaginatedResult
    {
        $data = [
            'start' => $start,
            'includeLabels' => $includeLabels ? 'yes' : 'no',
        ];

        $response = $this->post('/domain/listAll', $data);

        /** @var array<array<string, mixed>> $responseDomains */
        $responseDomains = $response['domains'] ?? [];

        return new PaginatedResult(
            DomainCollection::fromArray($responseDomains),
            $start,
            self::PAGE_SIZE,
        );
    }

    /** @return Generator<int, Domain> */
    public function all(bool $includeLabels = false): Generator
    {
        $start = 0;

        do {
            $page = $this->list($start, $includeLabels);

            foreach ($page as $domain) {
                yield $domain;
            }

            $start = $page->nextStart ?? 0;
        } while ($page->hasMore);
    }

    /** @return list<AutoRenewResult> */
    public function enableAutoRenew(string $domain, string ...$moreDomains): array
    {
        return $this->updateAutoRenew(true, $domain, ...$moreDomains);
    }

    /** @return list<AutoRenewResult> */
    public function disableAutoRenew(string $domain, string ...$moreDomains): array
    {
        return $this->updateAutoRenew(false, $domain, ...$moreDomains);
    }

    /** @return list<AutoRenewResult> */
    private function updateAutoRenew(bool $enable, string $domain, string ...$moreDomains): array
    {
        $allDomains = [$domain, ...$moreDomains];

        $endpoint = '/domain/updateAutoRenew';
        $data = ['status' => $enable ? 'on' : 'off'];

        if (count($allDomains) === 1) {
            $endpoint .= '/' . $allDomains[0];
        } else {
            $data['domains'] = $allDomains;
        }

        $response = $this->post($endpoint, $data);

        /** @var array<string, array{status: string, message?: string}> $results */
        $results = $response['results'] ?? [];

        return AutoRenewResult::fromResults($results);
    }
}
