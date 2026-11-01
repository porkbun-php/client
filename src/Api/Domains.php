<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Generator;
use Porkbun\DTO\AutoRenewResult;
use Porkbun\DTO\Domain;
use Porkbun\DTO\DomainCollection;
use Porkbun\DTO\PaginatedResult;
use Porkbun\DTO\RegistrationRequirements;
use Porkbun\DTO\TransferRecordCollection;
use Porkbun\Exception\ApiException;

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

    public function registrationRequirements(string $tld): RegistrationRequirements
    {
        $httpClient = $this->context->httpClient();
        $data = $httpClient->get("/domain/getRegistrationRequirements/{$tld}");

        if (!is_string($data['tld'] ?? null) || !is_array($data['requestSchema'] ?? null)) {
            throw new ApiException(
                'Invalid API response: missing registration requirements',
                $httpClient->lastResponse?->getStatusCode() ?? 0,
                response: $httpClient->lastResponse,
            );
        }

        return RegistrationRequirements::fromArray($data);
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

    public function transfers(): TransferRecordCollection
    {
        $httpClient = $this->context->httpClient();
        $data = $httpClient->get('/domain/listTransfers');

        if (!is_array($data['transfers'] ?? null)) {
            throw new ApiException(
                'Invalid API response: missing transfers',
                $httpClient->lastResponse?->getStatusCode() ?? 0,
                response: $httpClient->lastResponse,
            );
        }

        return TransferRecordCollection::fromArray($data['transfers']);
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
