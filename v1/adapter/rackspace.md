---
layout: default
permalink: /v1/docs/adapter/rackspace/
redirect_from:
    - /docs/adapter/rackspace/
    - /adapter/rackspace/
title: Rackspace Adapter
---

## Installation

```bash
composer require league/flysystem-rackspace
```

## Usage

```php
use OpenCloud\OpenStack;
use OpenCloud\Rackspace;
use League\Flysystem\Filesystem;
use League\Flysystem\Rackspace\RackspaceAdapter;

$client = new OpenStack(Rackspace::UK_IDENTITY_ENDPOINT, [
    'username' => ':username',
    'password' => ':password',
]);

$store = $client->objectStoreService('cloudFiles', 'LON');
$container = $store->getContainer('flysystem');

$filesystem = new Filesystem(new RackspaceAdapter($container, 'optional/path/prefix'));
```
