<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\SslCertificate;
use Porkbun\HttpClient;

final class Ssl extends AbstractApi
{
    public function __construct(
        HttpClient $httpClient,
        private readonly string $domain,
    ) {
        parent::__construct($httpClient);
    }

    public function retrieve(): SslCertificate
    {
        $response = $this->post("/ssl/retrieve/{$this->domain}");

        return SslCertificate::fromArray($response);
    }

    public function getDomain(): string
    {
        return $this->domain;
    }
}
