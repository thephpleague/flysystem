---
layout: default
permalink: /docs/adapter/scaleway-object-storage/
redirect_from: /adapter/scaleway-object-storage/
title: Scaleway Object Storage
---

The Scaleway Object Storage api are compatible with those of S3, from Flysystem's perspective this means you can use the
`league/flysystem-aws-s3-v3` adapter.

## Installation

~~~ bash
composer require league/flysystem-aws-s3-v3
~~~

## Usage

```php
use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;

$client = new S3Client([
    'credentials' => [
        'key'    => 'your-key',
        'secret' => 'your-secret',
    ],
    'region' => 'your-region',
    'version' => 'latest|version',
    'endpoint' => 'https://s3.your-region.scw.cloud',
]);

$adapter = new AwsS3Adapter($client, 'your-bucket-name');

$filesystem = new Filesystem($adapter);
```