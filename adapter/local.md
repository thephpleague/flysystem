---
layout: default
permalink: /adapter/local/
title: Local Adapter
---

# Local Adapter

## Installation

Comes with the main Flysystem package.

## Usage

~~~ php
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

$adapter = new Local(__DIR__.'/path/to/root');
$filesystem = new Filesystem($adapter);
~~~

## Locks

By default this adapter uses a lock during writes
and updates. This behaviour can be altered using the
second constructor argument.

~~~ php
$adapter = new Local(__DIR__.'/path/to/too', 0);
~~~

## Links

The Local adapter doesn't support links, this violates
the root path constraint which is enforces throughout
Flysystem. By default, when links are encountered an
exception is thrown. This behaviour can be altered
using the third constructor argument.

~~~ php
// Skip links
$adapter = new Local(__DIR__.'/path/to/too', LOCK_EX, Local::SKIP_LINKS);

// Throw exceptions (default)
$adapter = new Local(__DIR__.'/path/to/too', LOCK_EX, Local::DISALLOW_LINKS);
~~~
