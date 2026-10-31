<?php

declare(strict_types=1);

use Porkbun\Api\Contacts;
use Porkbun\DTO\Contact;
use Porkbun\DTO\ContactsUpdateResult;
use Porkbun\DTO\DomainContacts;
use Porkbun\Enum\AddressValidationChoice;
use Porkbun\Exception\InvalidArgumentException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

function contactFixture(string $firstName = 'Test', string $email = 'test@example.com'): array
{
    return [
        'firstName' => $firstName,
        'lastName' => 'User',
        'organization' => '',
        'address1' => '1 Example St',
        'address2' => '',
        'address3' => '',
        'city' => 'Springfield',
        'state' => 'Illinois',
        'postalCode' => '62701',
        'country' => 'US',
        'phone' => '5555550100',
        'phoneCountryCode' => '1',
        'fax' => '',
        'email' => $email,
    ];
}

/** @param callable(array<string, mixed>): void $assertBody */
function contactsApiExpectingBody(callable $assertBody): Contacts
{
    $mock = Mockery::mock(ClientInterface::class);

    $mock->shouldReceive('sendRequest')
        ->once()
        ->with(Mockery::on(function (RequestInterface $request) use ($assertBody): bool {
            expect((string) $request->getUri())->toContain('/domain/updateContacts/example.com');

            $assertBody(json_decode((string) $request->getBody(), true));

            return true;
        }))
        ->andReturn(createMockResponse(json_encode([
            'status' => 'SUCCESS',
            'message' => 'Contacts updated.',
            'registrantChanged' => false,
        ])));

    return new Contacts(createMockContext(createHttpClient($mock, 'pk1_key', 'sk1_secret')), 'example.com');
}

test('contacts api can get all contacts', function (): void {
    $mockClient = createMockHttpClient([
        [
            'status' => 'SUCCESS',
            'contacts' => [
                'registrant' => contactFixture(),
                'admin' => contactFixture('Other', 'admin@example.com'),
                'tech' => contactFixture(),
                'billing' => contactFixture(),
            ],
        ],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $contacts = new Contacts(createMockContext($httpClient), 'example.com');

    $domainContacts = $contacts->all();

    expect($domainContacts)->toBeInstanceOf(DomainContacts::class)
        ->and($domainContacts->registrant?->firstName)->toBe('Test')
        ->and($domainContacts->admin?->email)->toBe('admin@example.com')
        ->and($domainContacts->tech?->state)->toBe('Illinois')
        ->and($domainContacts->billing?->phoneCountryCode)->toBe('1');
});

test('contacts api returns empty contacts for an empty list', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS', 'contacts' => []],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $contacts = new Contacts(createMockContext($httpClient), 'example.com');

    $domainContacts = $contacts->all();

    expect($domainContacts->registrant)->toBeNull()
        ->and($domainContacts->admin)->toBeNull()
        ->and($domainContacts->tech)->toBeNull()
        ->and($domainContacts->billing)->toBeNull();
});

test('contacts api returns empty contacts when the key is missing', function (): void {
    $mockClient = createMockHttpClient([
        ['status' => 'SUCCESS'],
    ]);

    $httpClient = createHttpClient($mockClient, 'pk1_key', 'sk1_secret');
    $contacts = new Contacts(createMockContext($httpClient), 'example.com');

    expect($contacts->all()->toArray())->toBe([]);
});

test('contacts api updates only the given roles', function (): void {
    $contacts = contactsApiExpectingBody(function (array $body): void {
        expect($body['contacts'])->toBe(['admin' => contactFixture('Other', 'admin@example.com')])
            ->and($body)->not->toHaveKey('contact')
            ->and($body)->not->toHaveKey('addressValidationChoice');
    });

    $result = $contacts->update(admin: Contact::fromArray(contactFixture('Other', 'admin@example.com')));

    expect($result)->toBeInstanceOf(ContactsUpdateResult::class)
        ->and($result->registrantChanged)->toBeFalse()
        ->and($result->message)->toBe('Contacts updated.');
});

test('contacts api sends every given role', function (): void {
    $contacts = contactsApiExpectingBody(function (array $body): void {
        expect($body['contacts'])->toBe([
            'admin' => contactFixture('Admin', 'admin@example.com'),
            'tech' => contactFixture('Tech', 'tech@example.com'),
        ]);
    });

    $contacts->update(
        admin: Contact::fromArray(contactFixture('Admin', 'admin@example.com')),
        tech: Contact::fromArray(contactFixture('Tech', 'tech@example.com')),
    );
});

test('contacts api updates all roles with one contact', function (): void {
    $contacts = contactsApiExpectingBody(function (array $body): void {
        expect($body['contact'])->toBe(contactFixture())
            ->and($body)->not->toHaveKey('contacts');
    });

    $contacts->updateAll(Contact::fromArray(contactFixture()));
});

test('contacts api sends address validation choice', function (): void {
    $contacts = contactsApiExpectingBody(function (array $body): void {
        expect($body['addressValidationChoice'])->toBe('accept_suggestion');
    });

    $contacts->update(
        registrant: Contact::fromArray(contactFixture()),
        addressValidationChoice: AddressValidationChoice::ACCEPT_SUGGESTION,
    );
});

test('contacts api accepts address validation choice as string', function (): void {
    $contacts = contactsApiExpectingBody(function (array $body): void {
        expect($body['addressValidationChoice'])->toBe('use_as_entered');
    });

    $contacts->updateAll(Contact::fromArray(contactFixture()), 'use_as_entered');
});

test('contacts api requires at least one role to update', function (): void {
    $mockClient = createMockHttpClient([]);
    $contacts = new Contacts(createMockContext(createHttpClient($mockClient, 'pk1_key', 'sk1_secret')), 'example.com');

    expect(fn (): ContactsUpdateResult => $contacts->update())
        ->toThrow(InvalidArgumentException::class, 'At least one contact role is required');
});
