---
layout: default
title: AsyncAws S3 Adapter
permalink: /v2/docs/adapter/async-aws-s3/
---

## Installation

```bash
composer require league/flysystem-async-aws-s3
```

## About

Interacting with Aws S3 through Flysystem can be done
by using the `League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter`.

Read more about AsyncAws's S3Client in [their documentation](https://async-aws.com/clients/s3.html).

## Simple usage:

```php
$client = new AsyncAws\S3\S3Client();

// The internal adapter
$adapter = new League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter(
    // S3Client
    $client,
    // Bucket name
    'bucket-name'
);

// The FilesystemOperator
$filesystem = new League\Flysystem\Filesystem($adapter);
```

## Advanced usage:

```php
$client = new AsyncAws\S3\S3Client();

// The internal adapter
$adapter = new League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter(
    // S3Client
    $client,
    // Bucket name
    'bucket-name',
    // Optional path prefix
    'path/prefix',
    // Visibility converter (League\Flysystem\AsyncAwsS3\VisibilityConverter)
    new League\Flysystem\AsyncAwsS3\PortableVisibilityConverter(
        // Optional default for directories
        League\Flysystem\Visibility::PUBLIC // or ::PRIVATE
    )
);

// The FilesystemOperator
$filesystem = new League\Flysystem\Filesystem($adapter);
```

## Support for large files:

If you want to upload files larger than 5GB you need to use the `SimpleS3Client`.
The `SimpleS3Client` automatically switches to MultipartUpload for large files. It
also supports a user-friendly interface to `upload()`, `download()`, `getUrl()` etc
if you happen to use the client without Flysystem.

```cli
composer require async-aws/simple-s3
```

```php
$client = new AsyncAws\SimpleS3\SimpleS3Client();
$adapter = new League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter($client, 'bucket-name');
$filesystem = new League\Flysystem\Filesystem($adapter);
```
