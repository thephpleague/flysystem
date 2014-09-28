---
layout: default
permalink: /adapter/dropbox/
title: Dropbox Adapter
---

# Dropbox Adapter

~~~ php
use Dropbox\Client;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Dropbox as Adapter;

$client = new Client($token, $appName);
$filesystem = new Filesystem(new Adapter($client, 'optional/path/prefix'));
~~~
