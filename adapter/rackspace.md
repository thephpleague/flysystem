---
layout: default
permalink: /adapter/rackspace/
title: Rackspace Adapter
---

# Rackspace Adapter

~~~ php
use OpenCloud\OpenStack;
use OpenCloud\Rackspace;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Rackspace as Adapter;

$client = new OpenStack(Rackspace::UK_IDENTITY_ENDPOINT, array(
    'username' => ':username',
    'password' => ':password',
));

$store = $client->objectStoreService('cloudFiles', 'LON');
$container = $store->getContainer('flysystem');

$filesystem = new Filesystem(new Adapter($container));
~~~
