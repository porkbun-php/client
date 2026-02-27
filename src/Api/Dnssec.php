<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\DnssecRecordCollection;
use Porkbun\Internal\ClientContext;

final class Dnssec extends AbstractApi
{
    public function __construct(
        ClientContext $clientContext,
        private readonly string $domain,
    ) {
        parent::__construct($clientContext);
    }

    public function all(): DnssecRecordCollection
    {
        $response = $this->post("/dns/getDnssecRecords/{$this->domain}");

        /** @var array<array<string, mixed>> $responseRecords */
        $responseRecords = $response['records'] ?? [];

        return DnssecRecordCollection::fromArray($responseRecords);
    }

    public function create(
        int|string $keyTag,
        int|string $algorithm,
        int|string $digestType,
        string $digest,
        int|string|null $maxSigLife = null,
        int|string|null $flags = null,
        int|string|null $protocol = null,
        ?string $publicKey = null,
    ): void {
        $data = [
            'keyTag' => (string) $keyTag,
            'alg' => (string) $algorithm,
            'digestType' => (string) $digestType,
            'digest' => $digest,
        ];

        if ($maxSigLife !== null) {
            $data['maxSigLife'] = (string) $maxSigLife;
        }

        if ($flags !== null) {
            $data['keyDataFlags'] = (string) $flags;
        }

        if ($protocol !== null) {
            $data['keyDataProtocol'] = (string) $protocol;
        }

        if ($publicKey !== null) {
            $data['keyDataAlgo'] = (string) $algorithm;
            $data['keyDataPubKey'] = $publicKey;
        }

        $this->post("/dns/createDnssecRecord/{$this->domain}", $data);
    }

    public function delete(int|string $keyTag): void
    {
        $this->post("/dns/deleteDnssecRecord/{$this->domain}/{$keyTag}");
    }
}
