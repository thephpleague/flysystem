---
layout: layout
title: Caching
---

# Caching

To improve performance, Flysystem has caching built in. Meta information exposed by
the different adapters is normalized and stored. This prevents you from hitting the
filesystem or API across multiple requests, resulting in faster page loads or job
executions.

## Memory

This is the implicid caching strategy. It caches data for a single request.

~~~.language-php
use League\Flysystem\Filesystem;
use League\Flysystem\Cache\Memory as Cache;

$filesystem = new Filesystem($adapter, new Cache);
~~~

## Memcached

~~~.language-php
use League\Flysystem\Filesystem;
use League\Flysystem\Cache\Memcached as Cache;

$memcached = new Memcached;
$memcached->addServer('localhost', 11211);
$filesystem = new Filesystem($adapter, new Cache($memcached, 'storageKey', 300));
// Storage Key and expire time are optional
~~~

## Redis (through Predis)

~~~.language-php
use League\Flysystem\Filesystem;
use League\Flysystem\Cache\Predis as Cache;

$filesystem = new Filesystem($adapter, new Cache);

// Or supply a client
$client = new Predis\Client;
$filesystem = new Filesystem($adapter, new Cache($client));
~~~

## Noop

This strategy prevents any kind of caching, even in the current request. Use with caution!

~~~.language-php
use League\Flysystem\Filesystem;
use League\Flysystem\Cache\Noop as Cache;

$filesystem = new Filesystem($adapter, new Cache);
~~~