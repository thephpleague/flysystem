---
layout: default
title: Google Cloud Storage
permalink: /docs/adapter/google-cloud-storage/

---

## Installation

```bash
composer require league/flysystem-google-cloud-storage:^3.0
```

## Notice

It's important to know this adapter does not fully comply with the adapter contract. The difference(s) is/are:

- Visibility retrieving for unknown files always resolved to private.

## Usage

```php
<?php

use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter;
use League\Flysystem\Filesystem;
use Google\Cloud\Storage\StorageClient;

include __DIR__.'/vendor/autoload.php';

$storageClient = new StorageClient($clientOptions);
$bucket = $storageClient->bucket('your-bucket-name');

$adapter = new GoogleCloudStorageAdapter($bucket, 'optional-prefix');

$filesystem = new Filesystem($adapter);
```

