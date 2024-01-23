---
layout: default
title: Aws S3 (v3) Adapter
permalink: /docs/adapter/aws-s3-v3/
redirect_from: /v2/docs/adapter/aws-s3-v3/
---

## Installation

```bash
composer require league/flysystem-aws-s3-v3:^3.0
```

## About

Interacting with Aws S3 through Flysystem can be done
by using the `League\Flysystem\AwsS3V3\AwsS3V3Adapter`.

## Simple usage:

```php
/** @var Aws\S3\S3ClientInterface $client */
$client = new Aws\S3\S3Client($options);

// The internal adapter
$adapter = new League\Flysystem\AwsS3V3\AwsS3V3Adapter(
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
/** @var Aws\S3\S3ClientInterface $client */
$client = new Aws\S3\S3Client($options);

// The internal adapter
$adapter = new League\Flysystem\AwsS3V3\AwsS3V3Adapter(
    // S3Client
    $client,
    // Bucket name
    'bucket-name',
    // Optional path prefix
    'path/prefix',
    // Visibility converter (League\Flysystem\AwsS3V3\VisibilityConverter)
    new League\Flysystem\AwsS3V3\PortableVisibilityConverter(
        // Optional default for directories
        League\Flysystem\Visibility::PUBLIC // or ::PRIVATE
    )
);

// The FilesystemOperator
$filesystem = new League\Flysystem\Filesystem($adapter);
```

## IAM Permissions

The required IAM permissions are as followed:

~~~ json
 {
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "s3:ListBucket",
                "s3:ListObjects",
                "s3:GetObject",
                "s3:DeleteObject",
                "s3:GetObjectAcl",
                "s3:PutObjectAcl",
                "s3:PutObject"
            ],
            "Resource": [
                "arn:aws:s3:::your-bucket-name",
                "arn:aws:s3:::your-bucket-name/*"
            ]
        }
    ]
}
~~~
