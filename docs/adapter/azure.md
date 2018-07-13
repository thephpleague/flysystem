---
layout: default
permalink: /docs/adapter/azure/
redirect_from: /adapter/azure/
title: Azure Blob Storage
---

## Installation

```bash
composer require league/flysystem-azure-blob-storage
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

## Sponsored by:

<div class="flex my-6">
    <a target="_blank" href="https://azure.microsoft.com/free/?utm_source=flysystem&utm_medium=banner&utm_campaign=flysystem_sponsorship" class="flex-no-grow w-1/3 bg-white rounded shadow-md mr-4 overflow-hidden">
        <img src="/img/azure.svg" class="max-w-full m-6 sm:m-8" alt="Azure.com"/>
        <span style="background-color: #00a1f1;" class="text-center text-xl hidden sm:block py-4 bg-indigo-dark text-white bg-grey-lightest">Azure.com</span>
    </a>
</div>
