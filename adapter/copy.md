---
layout: default
permalink: /adapter/copy/
title: Copy.com Adapter
---

# Copy.com Adapter

~~~ php
use Barracuda\Copy\API;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Copy as Adapter;

$client = new API($consumerKey, $consumerSecret, $accessToken, $tokenSecret);
$filesystem = new Filesystem(new Adapter($client, 'optional/path/prefix'));
~~~
