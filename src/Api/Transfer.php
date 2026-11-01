<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\DomainTransfer;
use Porkbun\DTO\TransferAuthCodeUpdate;
use Porkbun\DTO\TransferCancellation;
use Porkbun\DTO\TransferRecord;
use Porkbun\Exception\ApiException;
use Porkbun\Internal\ClientContext;

final class Transfer extends AbstractApi
{
    public function __construct(
        ClientContext $clientContext,
        private readonly string $domain,
    ) {
        parent::__construct($clientContext);
    }

    /**
     * @param int $costInCents Expected cost in cents (from availability check)
     */
    public function initiate(string $authCode, int $costInCents): DomainTransfer
    {
        $data = $this->post("/domain/transfer/{$this->domain}", [
            'authCode' => $authCode,
            'cost' => $costInCents,
        ]);

        if (!isset($data['orderId'])) {
            throw $this->invalidResponse('missing transfer order');
        }

        return DomainTransfer::fromArray($data);
    }

    public function details(): TransferRecord
    {
        $data = $this->context->httpClient()->get("/domain/getTransfer/{$this->domain}");

        if (!is_array($data['transfer'] ?? null)) {
            throw $this->invalidResponse('missing transfer');
        }

        return TransferRecord::fromArray($data['transfer']);
    }

    public function cancel(): TransferCancellation
    {
        return TransferCancellation::fromArray($this->post("/domain/cancelTransfer/{$this->domain}"));
    }

    public function updateAuthCode(string $authCode): TransferAuthCodeUpdate
    {
        $data = $this->post("/domain/updateTransferAuthCode/{$this->domain}", [
            'authCode' => $authCode,
        ]);

        return TransferAuthCodeUpdate::fromArray($data);
    }

    private function invalidResponse(string $reason): ApiException
    {
        $lastResponse = $this->context->httpClient()->lastResponse;

        return new ApiException(
            "Invalid API response: {$reason}",
            $lastResponse?->getStatusCode() ?? 0,
            response: $lastResponse,
        );
    }
}
