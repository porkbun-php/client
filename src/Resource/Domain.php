<?php

declare(strict_types=1);

namespace Porkbun\Resource;

use Porkbun\Api\AutoRenew;
use Porkbun\Api\Availability as AvailabilityApi;
use Porkbun\Api\Dns;
use Porkbun\Api\Dnssec;
use Porkbun\Api\Domains;
use Porkbun\Api\GlueRecords;
use Porkbun\Api\Nameservers;
use Porkbun\Api\Registration as RegistrationApi;
use Porkbun\Api\UrlForwarding;
use Porkbun\DTO\AvailabilityResult;
use Porkbun\DTO\Domain as DomainDto;
use Porkbun\DTO\DomainRegistration;
use Porkbun\DTO\SslCertificate;
use Porkbun\Exception\ApiException;
use Porkbun\Internal\ClientContext;

final class Domain
{
    private ?Dns $dns = null;

    private ?Dnssec $dnssec = null;

    private ?Nameservers $nameservers = null;

    private ?UrlForwarding $urlForwarding = null;

    private ?GlueRecords $glueRecords = null;

    private ?AutoRenew $autoRenew = null;

    public function __construct(
        public readonly string $name,
        private readonly ClientContext $clientContext,
        private readonly Domains $domains,
    ) {
    }

    public function details(): DomainDto
    {
        return $this->domains->find($this->name)
            ?? throw new ApiException("Domain '{$this->name}' not found in account", 0);
    }

    public function dns(): Dns
    {
        return $this->dns ??= new Dns($this->clientContext, $this->name);
    }

    public function dnssec(): Dnssec
    {
        return $this->dnssec ??= new Dnssec($this->clientContext, $this->name);
    }

    public function ssl(): SslCertificate
    {
        $data = $this->clientContext->httpClient()->post("/ssl/retrieve/{$this->name}");

        return SslCertificate::fromArray($data);
    }

    public function nameservers(): Nameservers
    {
        return $this->nameservers ??= new Nameservers($this->clientContext, $this->name);
    }

    public function urlForwarding(): UrlForwarding
    {
        return $this->urlForwarding ??= new UrlForwarding($this->clientContext, $this->name);
    }

    public function glueRecords(): GlueRecords
    {
        return $this->glueRecords ??= new GlueRecords($this->clientContext, $this->name);
    }

    public function autoRenew(): AutoRenew
    {
        return $this->autoRenew ??= new AutoRenew($this->clientContext, $this->name);
    }

    public function check(): AvailabilityResult
    {
        return new AvailabilityApi($this->clientContext, $this->name)->check();
    }

    public function register(int $costInCents): DomainRegistration
    {
        return new RegistrationApi($this->clientContext, $this->name)->register($costInCents);
    }
}
