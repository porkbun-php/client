<?php

declare(strict_types=1);

namespace Porkbun\Service;

class AuthService extends AbstractService
{
    public function ping(): array
    {
        return $this->post('/ping');
    }

    protected function requiresAuth(): bool
    {
        return true;
    }
}
