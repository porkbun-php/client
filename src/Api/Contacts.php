<?php

declare(strict_types=1);

namespace Porkbun\Api;

use Porkbun\DTO\Contact;
use Porkbun\DTO\ContactsUpdateResult;
use Porkbun\DTO\DomainContacts;
use Porkbun\Enum\AddressValidationChoice;
use Porkbun\Exception\InvalidArgumentException;
use Porkbun\Internal\ClientContext;

final class Contacts extends AbstractApi
{
    public function __construct(
        ClientContext $clientContext,
        private readonly string $domain,
    ) {
        parent::__construct($clientContext);
    }

    public function all(): DomainContacts
    {
        $response = $this->post("/domain/getContacts/{$this->domain}");

        return DomainContacts::fromArray(is_array($response['contacts'] ?? null) ? $response['contacts'] : []);
    }

    public function update(
        ?Contact $registrant = null,
        ?Contact $admin = null,
        ?Contact $tech = null,
        ?Contact $billing = null,
        string|AddressValidationChoice|null $addressValidationChoice = null,
    ): ContactsUpdateResult {
        $contacts = new DomainContacts($registrant, $admin, $tech, $billing)->toArray();

        if ($contacts === []) {
            throw new InvalidArgumentException('At least one contact role is required');
        }

        return $this->updateContacts(['contacts' => $contacts], $addressValidationChoice);
    }

    public function updateAll(
        Contact $contact,
        string|AddressValidationChoice|null $addressValidationChoice = null,
    ): ContactsUpdateResult {
        return $this->updateContacts(['contact' => $contact->toArray()], $addressValidationChoice);
    }

    private function updateContacts(array $data, string|AddressValidationChoice|null $addressValidationChoice): ContactsUpdateResult
    {
        if ($addressValidationChoice !== null) {
            $data['addressValidationChoice'] = AddressValidationChoice::resolve($addressValidationChoice)->value;
        }

        $response = $this->post("/domain/updateContacts/{$this->domain}", $data);

        return ContactsUpdateResult::fromArray($response);
    }
}
