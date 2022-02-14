---
layout: default
title: Azure Blob Storage Adapter
permalink: /docs/adapter/azure-blob-storage/
redirect_from: 
    - /docs/adapter/azure/
---

## Installation

```bash
composer require league/flysystem-azure-blob-storage
```

## Notice

It's important to know this adapter does not fully comply with the adapter contract. The difference(s) is/are:

- Visibility setting or retrieving is not supported.
- Mimetypes are _always_ resolved, where others do not.
- Directory creation is not supported in any way.

## Usage

```php
<?php

use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use League\Flysystem\Filesystem;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

include __DIR__.'/vendor/autoload.php';

$client = BlobRestProxy::createBlobService('[INSERT-DSN-STRING-HERE]');
$adapter = new AzureBlobStorageAdapter(
    $client,
    'container-name',
    'optional/prefix',
);
$filesystem = new Filesystem($adapter);
```

