---
layout: default
permalink: /caching/
title: Caching
---

# Caching

Filesystem I/O is slow, so Flysystems cached filesystem meta-data to boost performance. By default this is done on a per session/request basis. When your application needs to scale you can also choose to use a (shared) persistent caching solution for this.

Flysystem caches anything but the file contents. This keeps the cache small enough to be benefitial and covers all the filesystem inspection operations.

The following examples demonstrate how you can setup persistent meta-data caching:

## Predis Caching Setup

~~~ php
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local as Adapter;
use League\Flysystem\Cache\Predis as Cache;

$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/root'), new Cache);

// Or supply a client
$client = new Predis\Client;
$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/root'), new Cache($client));
~~~

## Memcached Caching Setup

~~~ php
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local as Adapter;
use League\Flysystem\Cache\Memcached as Cache;

$memcached = new Memcached;
$memcached->addServer('localhost', 11211);

$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/root'), new Cache($memcached, 'storageKey', 300));
// Storage Key and expire time are optional
~~~

## Adapter Caching Setup

~~~ php
use Dropbox\Client;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Dropbox;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Cache\Adapter;

$client = new Client('token', 'app');
$dropbox = new Dropbox($client, 'prefix');

$local = new Local('path');
$cache = new Adapter($local, 'file', 300);
// Expire time is optional

$filesystem = new Filesystem($dropbox, $cache);
~~~