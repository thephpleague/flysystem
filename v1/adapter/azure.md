---
layout: default
permalink: /v1/docs/adapter/azure/
redirect_from:
    - /docs/adapter/azure/
    - /adapter/azure/
title: Azure Blob Storage
---

## Installation

```bash
composer require league/flysystem-azure-blob-storage:^1.0
```

## Usage

```php
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use League\Flysystem\Filesystem;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

include __DIR__.'/vendor/autoload.php';

$client = BlobRestProxy::createBlobService('DefaultEndpointsProtocol=https;AccountName={YOUR_ACCOUNT_NAME};AccountKey={YOUR_ACCOUNT_KEY};');
$adapter = new AzureBlobStorageAdapter($client, 'container_name');
$filesystem = new Filesystem($adapter);
var_dump($filesystem->listContents());
```
