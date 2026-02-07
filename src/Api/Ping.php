<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\PingData;

final class Ping extends AbstractApi
{
    public function check(): PingData
    {
        $data = $this->post('/ping');

        return PingData::fromArray($data);
    }
}
