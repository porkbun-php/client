<?php

declare(strict_types=1);

use Porkbun\DTO\RegistrationRequirements;

test('it creates registration requirements for a registrant-only tld', function (): void {
    $requirements = RegistrationRequirements::fromArray(json_decode('{"status":"SUCCESS","tld":"de","apiRegisterable":true,"registrationDurationYears":1,"maxRegistrationYears":1,"whoisPrivacySupported":false,"requiresValidatedAddress":true,"registrantOnly":true,"requestSchema":{"$schema":"https://json-schema.org/draft/2020-12/schema","title":"domain/create request for .de","description":"Body for POST /api/json/v3/domain/create/{domain}.","type":"object","required":["apikey","secretapikey","cost","agreeToTerms"],"additionalProperties":false,"properties":{"apikey":{"type":"string","description":"Public API key (pk1_...)."},"secretapikey":{"type":"string","description":"Secret API key (sk1_...)."},"cost":{"type":"integer","minimum":0,"description":"Total price in US cents (integer)."},"agreeToTerms":{"type":"string","enum":["yes","1"],"description":"Must be \\"yes\\" to accept the registration agreement, ToS, Data Processing Addendum, privacy policy, and auto-renew terms."},"whoisPrivacy":{"type":"string","enum":["yes","no"],"description":"WHOIS privacy is NOT supported for this TLD; this field is ignored."}}},"registryRequirements":{"$schema":"https://json-schema.org/draft/2020-12/schema","title":"Registry eligibility data for .de","description":"Extra registrant/domain data the registry requires for this TLD.","type":"object","properties":{"registrantContactType":{"type":"string","title":"Is the owner a person or organization?","x-appliesTo":"registrant contact","enum":["PERSON","ORG"],"x-enumDescriptions":{"PERSON":"person","ORG":"organization"}}}},"requestId":"00000000-0000-0000-0000-000000000000","sandbox":true}', true));

    expect($requirements->tld)->toBe('de')
        ->and($requirements->apiRegisterable)->toBeTrue()
        ->and($requirements->registrantOnly)->toBeTrue()
        ->and($requirements->requiresValidatedAddress)->toBeTrue()
        ->and($requirements->whoisPrivacySupported)->toBeFalse()
        ->and($requirements->maxRegistrationYears)->toBe(1)
        ->and($requirements->registryRequirements['properties'] ?? null)->toHaveKey('registrantContactType')
        ->and($requirements->notApiRegisterableReason)->toBeNull()
        ->and($requirements->toArray())->not()->toHaveKey('notApiRegisterableReason');
});

test('it creates registration requirements for a tld the api cannot register', function (): void {
    $requirements = RegistrationRequirements::fromArray(json_decode('{"status":"SUCCESS","tld":"us","apiRegisterable":false,"registrationDurationYears":1,"maxRegistrationYears":null,"whoisPrivacySupported":false,"requiresValidatedAddress":true,"registrantOnly":false,"requestSchema":{"$schema":"https://json-schema.org/draft/2020-12/schema","title":"domain/create request for .us","description":"Body for POST /api/json/v3/domain/create/{domain}.","type":"object","required":["apikey","secretapikey","cost","agreeToTerms"],"additionalProperties":false,"properties":{"apikey":{"type":"string","description":"Public API key (pk1_...)."},"secretapikey":{"type":"string","description":"Secret API key (sk1_...)."},"cost":{"type":"integer","minimum":0,"description":"Total price in US cents (integer)."},"agreeToTerms":{"type":"string","enum":["yes","1"],"description":"Must be \\"yes\\" to accept the registration agreement, ToS, Data Processing Addendum, privacy policy, and auto-renew terms."},"whoisPrivacy":{"type":"string","enum":["yes","no"],"description":"WHOIS privacy is NOT supported for this TLD; this field is ignored."}}},"registryRequirements":{"$schema":"https://json-schema.org/draft/2020-12/schema","title":"Registry eligibility data for .us","description":"Extra registrant/domain data the registry requires for this TLD.","type":"object","properties":{"purpose":{"type":"string","title":"Purpose","x-appliesTo":"registrant contact","enum":["P1","P2","P3","P4","P5"],"x-enumDescriptions":{"P1":"Business use for profit.","P2":"Non-profit business, club, association, religious organization, etc.","P3":"Personal use.","P4":"Education purposes.","P5":"Government purposes."}},"category":{"type":"string","title":"Category","x-appliesTo":"registrant contact","enum":["C11","C12","C21","C31","C32"],"x-enumDescriptions":{"C11":"A natural person who is a United States citizen.","C12":"A natural person who is a permanent resident of the United States of America.","C21":"A US-based organization or company.","C31":"A foreign entity or organization that has a bona fide presence in the United States of America.","C32":"An entity that has an office or other facility in the United States."}}},"x-policyNote":"Warning To register this domain, you must accurately fill out the two drop-down menus in the \\"Additional Registration Requirements section.\\" You may be contacted by Nexus Compliance in the future to verify, for instance, that you are a US citizen."},"notApiRegisterableReason":"This TLD has registry eligibility requirements that the API cannot currently submit; register it through the website at https://porkbun.com.","requestId":"00000000-0000-0000-0000-000000000000","sandbox":true}', true));

    expect($requirements->apiRegisterable)->toBeFalse()
        ->and($requirements->notApiRegisterableReason)->toStartWith('This TLD has registry eligibility requirements')
        ->and($requirements->registryRequirements['properties'] ?? null)->toHaveKeys(['purpose', 'category'])
        ->and(RegistrationRequirements::fromArray($requirements->toArray()))->toEqual($requirements)
        ->and(json_encode($requirements))->toBe(json_encode($requirements->toArray()));
});
