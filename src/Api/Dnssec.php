<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\DnssecRecordCollection;
use Porkbun\DTO\OperationResult;
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
        int $keyTag,
        int $algorithm,
        int $digestType,
        string $digest,
        ?int $maxSigLife = null,
        ?int $flags = null,
        ?int $protocol = null,
        ?int $keyDataAlgorithm = null,
        ?string $publicKey = null,
    ): OperationResult {
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

        if ($keyDataAlgorithm !== null) {
            $data['keyDataAlgo'] = (string) $keyDataAlgorithm;
        }

        if ($publicKey !== null) {
            $data['keyDataPubKey'] = $publicKey;
        }

        $response = $this->post("/dns/createDnssecRecord/{$this->domain}", $data);

        return OperationResult::fromArray($response);
    }

    public function delete(int $keyTag): OperationResult
    {
        $response = $this->post("/dns/deleteDnssecRecord/{$this->domain}/{$keyTag}");

        return OperationResult::fromArray($response);
    }
}
