<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\Domain as DomainDto;
use Porkbun\DTO\DomainCheckData;
use Porkbun\DTO\GlueRecord;
use Porkbun\DTO\UrlForward;

final class Domain extends AbstractApi
{
    /** @return array<DomainDto> */
    public function listAll(int $start = 0, bool $includeLabels = false): array
    {
        $data = [
            'start' => $start,
            'includeLabels' => $includeLabels ? 'yes' : 'no',
        ];

        $response = $this->post('/domain/listAll', $data);

        /** @var array<array<string, mixed>> $responseDomains */
        $responseDomains = $response['domains'] ?? [];

        $domains = [];
        foreach ($responseDomains as $responseDomain) {
            $domains[] = DomainDto::fromArray($responseDomain);
        }

        return $domains;
    }

    public function check(string $domain): DomainCheckData
    {
        $response = $this->post("/domain/checkDomain/{$domain}");

        return DomainCheckData::fromArray($response);
    }

    /** @param array<string> $nameservers */
    public function updateNameservers(string $domain, array $nameservers): void
    {
        $data = [];
        foreach (array_values($nameservers) as $index => $nameserver) {
            $data['ns' . ($index + 1)] = $nameserver;
        }

        $this->post("/domain/updateNs/{$domain}", $data);
    }

    /** @return array<string> */
    public function getNameservers(string $domain): array
    {
        $response = $this->post("/domain/getNs/{$domain}");

        /** @var array<string> $nameservers */
        $nameservers = $response['ns'] ?? [];

        return $nameservers;
    }

    /** @param array{subdomain?: string, location: string, type: string, includePath?: string, wildcard?: string} $params */
    public function addUrlForward(string $domain, array $params): void
    {
        $this->post("/domain/addUrlForward/{$domain}", $params);
    }

    /** @return array<UrlForward> */
    public function getUrlForwards(string $domain): array
    {
        $response = $this->post("/domain/getUrlForwarding/{$domain}");

        /** @var array<array<string, mixed>> $responseForwards */
        $responseForwards = $response['forwards'] ?? [];

        $forwards = [];
        foreach ($responseForwards as $responseForward) {
            $forwards[] = UrlForward::fromArray($responseForward);
        }

        return $forwards;
    }

    public function deleteUrlForward(string $domain, int|string $recordId): void
    {
        $this->post("/domain/deleteUrlForward/{$domain}/{$recordId}");
    }

    /** @param array<string> $ips */
    public function createGlueRecord(string $domain, string $subdomain, array $ips): void
    {
        $this->post("/domain/createGlue/{$domain}/{$subdomain}", $this->buildIpData($ips));
    }

    /** @param array<string> $ips */
    public function updateGlueRecord(string $domain, string $subdomain, array $ips): void
    {
        $this->post("/domain/updateGlue/{$domain}/{$subdomain}", $this->buildIpData($ips));
    }

    public function deleteGlueRecord(string $domain, string $subdomain): void
    {
        $this->post("/domain/deleteGlue/{$domain}/{$subdomain}");
    }

    /** @return array<GlueRecord> */
    public function getGlueRecords(string $domain): array
    {
        $response = $this->post("/domain/getGlue/{$domain}");

        /** @var array<array<string, mixed>> $responseGlue */
        $responseGlue = $response['glue'] ?? [];

        $records = [];
        foreach ($responseGlue as $recordData) {
            $records[] = GlueRecord::fromArray($recordData);
        }

        return $records;
    }

    /**
     * @param array<string> $ips
     *
     * @return array<string, string>
     */
    private function buildIpData(array $ips): array
    {
        $data = [];
        foreach (array_values($ips) as $index => $ip) {
            $data['ip' . ($index + 1)] = $ip;
        }

        return $data;
    }
}
