<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\DomainRegistration;
use Porkbun\Exception\InvalidArgumentException;
use Porkbun\Internal\ClientContext;

final class Registration extends AbstractApi
{
    public function __construct(
        ClientContext $clientContext,
        private readonly string $domain,
    ) {
        parent::__construct($clientContext);
    }

    /**
     * @param int $cost Expected cost in cents (from availability check)
     * @param array{
     *     years?: int,
     *     coupon?: string,
     *     addPrivacy?: bool,
     *     ns?: array<string>,
     *     whois?: array{
     *         firstName?: string,
     *         lastName?: string,
     *         email?: string,
     *         phone?: string,
     *         address?: string,
     *         city?: string,
     *         state?: string,
     *         zip?: string,
     *         country?: string,
     *         organization?: string,
     *     }
     * } $options Additional registration options
     */
    public function register(int $cost, array $options = []): DomainRegistration
    {
        $allowedKeys = ['years', 'coupon', 'addPrivacy', 'ns', 'whois'];
        $unknownKeys = array_diff(array_keys($options), $allowedKeys);

        if ($unknownKeys !== []) {
            throw new InvalidArgumentException(
                'Unknown registration option(s): ' . implode(', ', $unknownKeys)
                . '. Allowed: ' . implode(', ', $allowedKeys)
            );
        }

        $data = [
            'cost' => $cost,
            'agreeToTerms' => 'yes',
        ];

        if (isset($options['years'])) {
            $data['years'] = $options['years'];
        }

        if (isset($options['coupon'])) {
            $data['coupon'] = $options['coupon'];
        }

        if (isset($options['addPrivacy'])) {
            $data['addPrivacy'] = $options['addPrivacy'] ? 'yes' : 'no';
        }

        if (isset($options['ns'])) {
            $data['ns'] = $options['ns'];
        }

        if (isset($options['whois'])) {
            $data['whois'] = $options['whois'];
        }

        $response = $this->post("/domain/create/{$this->domain}", $data);

        return DomainRegistration::fromArray($response);
    }
}
