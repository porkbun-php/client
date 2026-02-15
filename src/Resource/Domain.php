<?php

declare(strict_types=1);

namespace Porkbun\Resource;

use Porkbun\Api\AutoRenew;
use Porkbun\Api\Availability as AvailabilityApi;
use Porkbun\Api\Dns;
use Porkbun\Api\GlueRecords;
use Porkbun\Api\Nameservers;
use Porkbun\Api\Registration as RegistrationApi;
use Porkbun\Api\Ssl;
use Porkbun\Api\UrlForwarding;
use Porkbun\DTO\Availability;
use Porkbun\DTO\DomainRegistration;
use Porkbun\Internal\ClientContext;

final class Domain
{
    private ?AvailabilityApi $availabilityApi = null;

    private ?RegistrationApi $registrationApi = null;

    private ?Dns $dns = null;

    private ?Ssl $ssl = null;

    private ?Nameservers $nameservers = null;

    private ?UrlForwarding $urlForwarding = null;

    private ?GlueRecords $glueRecords = null;

    private ?AutoRenew $autoRenew = null;

    public function __construct(
        private readonly string $name,
        private readonly ClientContext $clientContext,
    ) {
    }

    public function dns(): Dns
    {
        return $this->dns ??= new Dns($this->clientContext, $this->name);
    }

    public function ssl(): Ssl
    {
        return $this->ssl ??= new Ssl($this->clientContext, $this->name);
    }

    public function nameservers(): Nameservers
    {
        return $this->nameservers ??= new Nameservers($this->clientContext, $this->name);
    }

    public function urlForwarding(): UrlForwarding
    {
        return $this->urlForwarding ??= new UrlForwarding($this->clientContext, $this->name);
    }

    public function glue(): GlueRecords
    {
        return $this->glueRecords ??= new GlueRecords($this->clientContext, $this->name);
    }

    public function autoRenew(): AutoRenew
    {
        return $this->autoRenew ??= new AutoRenew($this->clientContext, $this->name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function check(): Availability
    {
        $api = $this->availabilityApi ??= new AvailabilityApi($this->clientContext, $this->name);

        return $api->get();
    }

    /**
     * @param array{
     *     years?: int,
     *     coupon?: string,
     *     addPrivacy?: bool,
     *     ns?: array<string>,
     *     whois?: array<string, string>
     * } $options
     */
    public function register(int $cost, array $options = []): DomainRegistration
    {
        $api = $this->registrationApi ??= new RegistrationApi($this->clientContext, $this->name);

        return $api->register($cost, $options);
    }
}
