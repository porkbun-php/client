<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\SslCertificate;
use Porkbun\Internal\ClientContext;

final class Ssl extends AbstractApi
{
    public function __construct(
        ClientContext $clientContext,
        private readonly string $domain,
    ) {
        parent::__construct($clientContext);
    }

    public function get(): SslCertificate
    {
        $response = $this->post("/ssl/retrieve/{$this->domain}");

        return SslCertificate::fromArray($response);
    }
}
