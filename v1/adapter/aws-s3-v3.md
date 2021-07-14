---
layout: default
permalink: /v1/docs/adapter/aws-s3-v3/
redirect_from:
    - /v1/docs/adapter/aws-s3/
    - /v1/docs/adapter/aws-s3-v2/
    - /docs/adapter/aws-s3/
    - /adapter/aws-s3/
    - /adapter/aws-s3-v2/
    - /adapter/aws-s3-v3/
title: Aws S3 Adapter V3
---

## Installation

```bash
composer require league/flysystem-aws-s3-v3:^1.0
```

> **Note:** If you're using this adapter with Laravel 8 and below, make sure to require the 1.x version as shown above.
> For fresh installations, require this dependency using the `--with-all-dependencies` dependencies flag, otherwise
> composer will not be able to install it due to a misaligned underlying dependency.

## Usage

```php
use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;

$client = new S3Client([
    'credentials' => [
        'key'    => 'your-key',
        'secret' => 'your-secret',
    ],
    'region' => 'your-region',
    'version' => 'latest|version',
]);

$adapter = new AwsS3V3Adapter($client, 'your-bucket-name', 'optional/path/prefix');

$filesystem = new Filesystem($adapter);
```

### Streamed reads

Since 1.0.28, by default all readStream calls will result in a streamed HTTP response. This
makes it not possible to seek through the stream. You can disable streaming by using a constructor
argument:

```php
$adapter = new AwsS3Adapter($client, 'your-bucket-name', 'optional/path/prefix', [], false /** disable streamed reads **/);
```

The required IAM permissions are:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "Stmt1420044805001",
            "Effect": "Allow",
            "Action": [
                "s3:ListBucket",
                "s3:GetObject",
                "s3:GetObjectAcl",
                "s3:PutObject",
                "s3:PutObjectAcl",
                "s3:ReplicateObject",
                "s3:DeleteObject"
            ],
            "Resource": [
                "arn:aws:s3:::your-bucket-name",
                "arn:aws:s3:::your-bucket-name/*"
            ]
        }
    ]
}
```

To enable [reduced redundancy storage](http://aws.amazon.com/s3/details/#RRS) set up your adapter like so:

```php
$adapter = new AwsS3Adapter($client, 'bucket-name', 'optional/path/prefix', [
    'StorageClass'  =>  'REDUCED_REDUNDANCY',
]);
```

### Compatible storage protocols

If you're using a storage service which implements the S3 protocols, you can set the `base_url` configuration option when constructing the client.

```php
$client = new S3Client([
    'endpoint' => 'http://some.other.endpoint',
    // ... other settings
]);
```

### Default credential provider usage

If an IAM role is assigned to your EC2 instances, it is not necessary to specifically set environment or config based key and secret credentials. The default credential provider can be used by omitting `credentials` when creating an S3 client.

```php
use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;

// Credentials omitted. Default credential provider will be used
$client = new S3Client([
    'region' => 'your-region',
    'version' => 'latest|version',
]);

$adapter = new AwsS3Adapter($client, 'your-bucket-name', 'optional/path/prefix');

$filesystem = new Filesystem($adapter);
```

The default credential provider will attempt to load credentials from sources such as environment variables, configuration files and then from the instance profile, such as EC2 metadata.

For further details see https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials_provider.html#defaultprovider-provider

