<?php

declare(strict_types=1);

use Porkbun\DTO\Contact;

test('it creates contact from array', function (): void {
    $contact = Contact::fromArray([
        'firstName' => 'Test',
        'lastName' => 'User',
        'organization' => 'Example Org',
        'address1' => '1 Example St',
        'address2' => 'Suite 2',
        'address3' => 'Building 3',
        'city' => 'Springfield',
        'state' => 'Illinois',
        'postalCode' => '62701',
        'country' => 'US',
        'phone' => '5555550100',
        'phoneCountryCode' => '1',
        'fax' => '5555550199',
        'email' => 'test@example.com',
    ]);

    expect($contact->firstName)->toBe('Test')
        ->and($contact->lastName)->toBe('User')
        ->and($contact->organization)->toBe('Example Org')
        ->and($contact->address1)->toBe('1 Example St')
        ->and($contact->address2)->toBe('Suite 2')
        ->and($contact->address3)->toBe('Building 3')
        ->and($contact->city)->toBe('Springfield')
        ->and($contact->state)->toBe('Illinois')
        ->and($contact->postalCode)->toBe('62701')
        ->and($contact->country)->toBe('US')
        ->and($contact->phone)->toBe('5555550100')
        ->and($contact->phoneCountryCode)->toBe('1')
        ->and($contact->fax)->toBe('5555550199')
        ->and($contact->email)->toBe('test@example.com');
});

test('it casts numeric fields to strings', function (): void {
    $contact = Contact::fromArray(['postalCode' => 62701, 'phone' => 5555550100, 'phoneCountryCode' => 1]);

    expect($contact->postalCode)->toBe('62701')
        ->and($contact->phone)->toBe('5555550100')
        ->and($contact->phoneCountryCode)->toBe('1');
});

test('it defaults missing fields to empty strings', function (): void {
    $contact = Contact::fromArray(['firstName' => 'Test']);

    expect($contact->firstName)->toBe('Test')
        ->and($contact->lastName)->toBe('')
        ->and($contact->email)->toBe('')
        ->and($contact->fax)->toBe('');
});

test('toArray uses api field names', function (): void {
    $contact = new Contact(
        firstName: 'Test',
        address1: '1 Example St',
        city: 'Springfield',
        postalCode: '62701',
        country: 'US',
        phone: '5555550100',
        phoneCountryCode: '1',
        email: 'test@example.com',
    );

    expect($contact->toArray())->toBe([
        'firstName' => 'Test',
        'lastName' => '',
        'organization' => '',
        'address1' => '1 Example St',
        'address2' => '',
        'address3' => '',
        'city' => 'Springfield',
        'state' => '',
        'postalCode' => '62701',
        'country' => 'US',
        'phone' => '5555550100',
        'phoneCountryCode' => '1',
        'fax' => '',
        'email' => 'test@example.com',
    ]);
});

test('jsonSerialize returns toArray', function (): void {
    $contact = Contact::fromArray(['firstName' => 'Test', 'email' => 'test@example.com']);

    expect($contact->jsonSerialize())->toBe($contact->toArray());
});
