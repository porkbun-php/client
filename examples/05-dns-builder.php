<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Porkbun\Enum\DnsRecordType;

$apiKey = getenv('PORKBUN_API_KEY') ?: '';
$secretKey = getenv('PORKBUN_SECRET_KEY') ?: '';
$domainName = getenv('PORKBUN_DOMAIN') ?: '';

if ($apiKey === '' || $secretKey === '' || $domainName === '') {
    echo "Set PORKBUN_API_KEY, PORKBUN_SECRET_KEY, and PORKBUN_DOMAIN environment variables.\n";
    exit(1);
}

$client = new Porkbun\Client();
$client->authenticate($apiKey, $secretKey);
$dns = $client->domain($domainName)->dns();

// Convenience methods for common record types
// $dns->createFromBuilder($dns->record()->name('www')->a('192.0.2.1')->ttl(3600));
// $dns->createFromBuilder($dns->record()->name('v6')->aaaa('2001:db8::1'));
// $dns->createFromBuilder($dns->record()->name('blog')->cname('blog.provider.com'));
// $dns->createFromBuilder($dns->record()->name('mail')->mx('mail.provider.com', priority: 10));
// $dns->createFromBuilder($dns->record()->name('_dmarc')->txt('v=DMARC1; p=reject'));
// $dns->createFromBuilder($dns->record()->name('sub')->ns('ns1.provider.com'));
// $dns->createFromBuilder($dns->record()->name('')->caa('0 issue "letsencrypt.org"'));

// Full builder with all options
// $dns->createFromBuilder(
//     $dns->record()
//         ->name('api')
//         ->type(DnsRecordType::A)
//         ->content('192.0.2.2')
//         ->ttl(3600)
//         ->priority(0)
//         ->notes('API server')
// );

// Immutable template pattern — $base is never mutated
$base = $dns->record()->ttl(3600)->notes('Production');
$web1 = $base->name('web1')->a('10.0.1.1');
$web2 = $base->name('web2')->a('10.0.1.2');
$mail = $base->name('mail')->mx('mail.example.com', 10);

echo "Template-based records ready to create:\n";
echo "  web1: {$web1->data()['content']} (TTL {$web1->data()['ttl']})\n";
echo "  web2: {$web2->data()['content']} (TTL {$web2->data()['ttl']})\n";
echo "  mail: {$mail->data()['content']} (priority {$mail->data()['prio']})\n";

// Enum types work everywhere
$builder = $dns->record()->name('test')->type(DnsRecordType::A)->content('192.0.2.1');
echo "\nBuilder type: {$builder->data()['type']}\n";

// $dns->createFromBuilder($web1);
// $dns->createFromBuilder($web2);
// $dns->createFromBuilder($mail);
