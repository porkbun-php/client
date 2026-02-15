<?php

use Porkbun\Client;

$client = Client::create($apiKey, $secretKey);

// ── Auth ─────────────────────────────────────────────────────
// POST /ping
$client->ping();                                            // → PingResult

// ── Pricing (no auth required) ───────────────────────────────
// POST /pricing/get
$client->pricing()->all();                                  // → PricingCollection

// ── Domains (global) ─────────────────────────────────────────
// POST /domain/listAll
$client->domains()->all($start, $includeLabels);            // → DomainCollection
$client->domains()->allPages($includeLabels);               // → Generator<Domain>
$client->domains()->allCollections($includeLabels);         // → Generator<DomainCollection>

// POST /domain/updateAutoRenew  (bulk)
$client->domains()->enableAutoRenew('a.com', 'b.com');      // → array
$client->domains()->disableAutoRenew('a.com', 'b.com');     // → array

// ── Domain-scoped ────────────────────────────────────────────
$domain = $client->domain('example.com');

// POST /domain/checkDomain/{domain}
$domain->check();                                           // → Availability

// POST /domain/create/{domain}
$domain->register($cost, $options);                         // → DomainRegistration

// ── Auto-Renew ───────────────────────────────────────────────
// POST /domain/updateAutoRenew/{domain}
$domain->autoRenew()->enable();                             // → bool
$domain->autoRenew()->disable();                            // → bool

// ── Nameservers ──────────────────────────────────────────────
// POST /domain/getNs/{domain}
$domain->nameservers()->all();                              // → NameserverCollection

// POST /domain/updateNs/{domain}
$domain->nameservers()->update(['ns1.x.com', 'ns2.x.com']);// → void

// ── URL Forwarding ───────────────────────────────────────────
// POST /domain/getUrlForwarding/{domain}
$domain->urlForwarding()->all();                            // → UrlForwardCollection

// POST /domain/addUrlForward/{domain}
$domain->urlForwarding()->add($params);                     // → void

// POST /domain/deleteUrlForward/{domain}/{id}
$domain->urlForwarding()->delete($id);                      // → void

// ── Glue Records ─────────────────────────────────────────────
// POST /domain/getGlue/{domain}
$domain->glue()->all();                                     // → GlueRecordCollection

// POST /domain/createGlue/{domain}/{subdomain}
$domain->glue()->create('ns1', ['192.0.2.1']);              // → void

// POST /domain/updateGlue/{domain}/{subdomain}
$domain->glue()->update('ns1', ['192.0.2.10']);             // → void

// POST /domain/deleteGlue/{domain}/{subdomain}
$domain->glue()->delete('ns1');                             // → void

// ── DNS ──────────────────────────────────────────────────────
$dns = $domain->dns();

// POST /dns/retrieve/{domain}
$dns->all();                                                // → DnsRecordCollection

// POST /dns/retrieve/{domain}/{id}
$dns->find($id);                                            // → ?DnsRecord

// POST /dns/retrieveByNameType/{domain}/{type}/{subdomain?}
$dns->findByType($type, $subdomain);                        // → DnsRecordCollection

// POST /dns/create/{domain}
$dns->create($name, $type, $content, $ttl, $prio, $notes); // → CreateResult
$dns->createFromBuilder($builder);                          // → CreateResult

// POST /dns/edit/{domain}/{id}
$dns->edit($id, $data);                                     // → void

// POST /dns/editByNameType/{domain}/{type}/{subdomain?}
$dns->update($type, $subdomain, $data);                     // → void

// POST /dns/delete/{domain}/{id}
$dns->delete($id);                                          // → void

// POST /dns/deleteByNameType/{domain}/{type}/{subdomain?}
$dns->deleteByType($type, $subdomain);                      // → void

// ── DNSSEC ───────────────────────────────────────────────────
// POST /dns/getDnssecRecords/{domain}
$dns->getDnssecRecords();                                   // → DnssecRecordCollection

// POST /dns/createDnssecRecord/{domain}
$dns->createDnssec($params);                                // → void

// POST /dns/deleteDnssecRecord/{domain}/{keyTag}
$dns->deleteDnssec($keyTag);                                // → void

// ── SSL ──────────────────────────────────────────────────────
// POST /ssl/retrieve/{domain}
$domain->ssl()->get();                                      // → SslCertificate
