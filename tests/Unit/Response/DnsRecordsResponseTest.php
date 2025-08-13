<?php

declare(strict_types=1);

use Porkbun\Response\DnsRecordsResponse;

test('dns records response can get all records', function (): void {
    $data = [
        'status' => 'SUCCESS',
        'records' => [
            ['id' => '123', 'name' => 'www', 'type' => 'A', 'content' => '192.168.1.1'],
            ['id' => '124', 'name' => 'mail', 'type' => 'MX', 'content' => 'mail.example.com'],
        ],
    ];

    $response = new DnsRecordsResponse($data);

    expect($response->isSuccess())->toBeTrue();
    expect($response->getRecords())->toBe($data['records']);
    expect($response->hasRecords())->toBeTrue();
    expect($response->getRecordCount())->toBe(2);
});

test('dns records response can find record by id', function (): void {
    $data = [
        'status' => 'SUCCESS',
        'records' => [
            ['id' => '123', 'name' => 'www', 'type' => 'A', 'content' => '192.168.1.1'],
            ['id' => '124', 'name' => 'mail', 'type' => 'MX', 'content' => 'mail.example.com'],
        ],
    ];

    $response = new DnsRecordsResponse($data);

    $record = $response->getRecordById(123);
    expect($record)->toBe(['id' => '123', 'name' => 'www', 'type' => 'A', 'content' => '192.168.1.1']);

    $record = $response->getRecordById(999);
    expect($record)->toBeNull();
});

test('dns records response can filter by type', function (): void {
    $data = [
        'status' => 'SUCCESS',
        'records' => [
            ['id' => '123', 'name' => 'www', 'type' => 'A', 'content' => '192.168.1.1'],
            ['id' => '124', 'name' => 'mail', 'type' => 'MX', 'content' => 'mail.example.com'],
            ['id' => '125', 'name' => 'ftp', 'type' => 'A', 'content' => '192.168.1.2'],
        ],
    ];

    $response = new DnsRecordsResponse($data);

    $aRecords = $response->getRecordsByType('A');
    expect($aRecords)->toHaveCount(2);
    expect($aRecords[0]['name'])->toBe('www');
    expect($aRecords[2]['name'])->toBe('ftp');

    $mxRecords = $response->getRecordsByType('MX');
    expect($mxRecords)->toHaveCount(1);
    expect($mxRecords[1]['name'])->toBe('mail');
});

test('dns records response can filter by name', function (): void {
    $data = [
        'status' => 'SUCCESS',
        'records' => [
            ['id' => '123', 'name' => 'www', 'type' => 'A', 'content' => '192.168.1.1'],
            ['id' => '124', 'name' => 'www', 'type' => 'AAAA', 'content' => '2001:db8::1'],
            ['id' => '125', 'name' => 'mail', 'type' => 'MX', 'content' => 'mail.example.com'],
        ],
    ];

    $response = new DnsRecordsResponse($data);

    $wwwRecords = $response->getRecordsByName('www');
    expect($wwwRecords)->toHaveCount(2);
    expect($wwwRecords[0]['type'])->toBe('A');
    expect($wwwRecords[1]['type'])->toBe('AAAA');

    $mailRecords = $response->getRecordsByName('mail');
    expect($mailRecords)->toHaveCount(1);
    expect($mailRecords[2]['type'])->toBe('MX');
});

test('dns records response handles empty records', function (): void {
    $data = ['status' => 'SUCCESS'];

    $response = new DnsRecordsResponse($data);

    expect($response->getRecords())->toBe([]);
    expect($response->hasRecords())->toBeFalse();
    expect($response->getRecordCount())->toBe(0);
    expect($response->getRecordById(123))->toBeNull();
    expect($response->getRecordsByType('A'))->toBe([]);
    expect($response->getRecordsByName('www'))->toBe([]);
});

test('dns records response handles error status', function (): void {
    $data = [
        'status' => 'ERROR',
        'message' => 'Domain not found',
    ];

    $response = new DnsRecordsResponse($data);

    expect($response->isSuccess())->toBeFalse();
    expect($response->getStatus())->toBe('ERROR');
    expect($response->getMessage())->toBe('Domain not found');
});
