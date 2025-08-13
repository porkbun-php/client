<?php

declare(strict_types=1);

namespace Porkbun\Service;

class DomainService extends AbstractService
{
    public function listAll(int $start = 0, bool $includeLabels = false): array
    {
        $data = [];
        if ($start > 0) {
            $data['start'] = $start;
        }
        if ($includeLabels) {
            $data['includeLabels'] = 'yes';
        }

        return $this->post('/domain/listAll', $data);
    }

    public function checkDomain(string $domain): array
    {
        return $this->post("/domain/checkDomain/{$domain}");
    }

    public function updateNs(string $domain, array $nameservers): void
    {
        $this->post("/domain/updateNs/{$domain}", [
            'ns' => $nameservers,
        ]);
    }

    public function getNs(string $domain): array
    {
        return $this->post("/domain/getNs/{$domain}");
    }

    public function addUrlForward(string $domain, array $params): void
    {
        $this->post("/domain/addUrlForward/{$domain}", $params);
    }

    public function getUrlForwarding(string $domain): array
    {
        return $this->post("/domain/getUrlForwarding/{$domain}");
    }

    public function deleteUrlForward(string $domain, int $id): void
    {
        $this->post("/domain/deleteUrlForward/{$domain}/{$id}");
    }

    public function createGlue(string $domain, string $subdomain, array $ips): void
    {
        $this->post("/domain/createGlue/{$domain}/{$subdomain}", [
            'ips' => $ips,
        ]);
    }

    public function updateGlue(string $domain, string $subdomain, array $ips): void
    {
        $this->post("/domain/updateGlue/{$domain}/{$subdomain}", [
            'ips' => $ips,
        ]);
    }

    public function deleteGlue(string $domain, string $subdomain): void
    {
        $this->post("/domain/deleteGlue/{$domain}/{$subdomain}");
    }

    public function getGlue(string $domain): array
    {
        return $this->post("/domain/getGlue/{$domain}");
    }

    protected function requiresAuth(): bool
    {
        return true;
    }
}
