<?php

declare(strict_types=1);

use Porkbun\DTO\Contact;
use Porkbun\DTO\DomainContacts;

test('it creates domain contacts from array', function (): void {
    $domainContacts = DomainContacts::fromArray([
        'registrant' => ['firstName' => 'Owner'],
        'admin' => ['firstName' => 'Admin'],
        'tech' => ['firstName' => 'Tech'],
        'billing' => ['firstName' => 'Billing'],
    ]);

    expect($domainContacts->registrant)->toBeInstanceOf(Contact::class)
        ->and($domainContacts->registrant?->firstName)->toBe('Owner')
        ->and($domainContacts->admin?->firstName)->toBe('Admin')
        ->and($domainContacts->tech?->firstName)->toBe('Tech')
        ->and($domainContacts->billing?->firstName)->toBe('Billing');
});

test('it leaves missing roles null', function (): void {
    $domainContacts = DomainContacts::fromArray(['admin' => ['firstName' => 'Admin']]);

    expect($domainContacts->registrant)->toBeNull()
        ->and($domainContacts->admin?->firstName)->toBe('Admin')
        ->and($domainContacts->tech)->toBeNull()
        ->and($domainContacts->billing)->toBeNull();
});

test('toArray omits missing roles', function (): void {
    $admin = Contact::fromArray(['firstName' => 'Admin']);
    $domainContacts = new DomainContacts(admin: $admin);

    expect($domainContacts->toArray())->toBe(['admin' => $admin->toArray()]);
});

test('jsonSerialize returns toArray', function (): void {
    $domainContacts = DomainContacts::fromArray(['tech' => ['firstName' => 'Tech']]);

    expect($domainContacts->jsonSerialize())->toBe($domainContacts->toArray());
});
