---
layout: default
permalink: /docs/adapter/google-cloud-storage/
redirect_from: /adapter/google-cloud-storage/
title: Google Cloud Storage Adapter
---

## Installation

```bash
composer require superbalist/flysystem-google-storage
```

## Usage

Google Cloud Storage requires Service Account Credentials, which can be generated in the [Cloud Console](https://console.cloud.google.com/apis/credentials). Read more in [the official documentation](https://cloud.google.com/docs/authentication/production).

```php
use Google\Cloud\Storage\StorageClient;
use League\Flysystem\Filesystem;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;

/**
 * The credentials will be auto-loaded by the Google Cloud Client.
 *
 * 1. The client will first look at the GOOGLE_APPLICATION_CREDENTIALS env var.
 *    You can use ```putenv('GOOGLE_APPLICATION_CREDENTIALS=/path/to/service-account.json');``` to set the location of your credentials file.
 *
 * 2. The client will look for the credentials file at the following paths:
 * - windows: %APPDATA%/gcloud/application_default_credentials.json
 * - others: $HOME/.config/gcloud/application_default_credentials.json
 *
 * If running in Google App Engine, the built-in service account associated with the application will be used.
 * If running in Google Compute Engine, the built-in service account associated with the virtual machine instance will be used.
 */

$storageClient = new StorageClient([
    'projectId' => 'your-project-id',
]);
$bucket = $storageClient->bucket('your-bucket-name');

$adapter = new GoogleStorageAdapter($storageClient, $bucket);

$filesystem = new Filesystem($adapter);
```

See the [project README](https://github.com/Superbalist/flysystem-google-cloud-storage#usage) for additional usage examples.
