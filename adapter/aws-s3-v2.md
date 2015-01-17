---
layout: default
permalink: /adapter/aws-s3-v2/
title: Aws S3 Adapter V2
---

# Aws S3 Adapter - SDK V2

## Installation

~~~ bash
composer require league/flysystem-aws-s3-v2
~~~

~~~ php
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V2\AwsS3Adapter;
use League\Flysystem\Filesystem;

$client = S3Client::factory(array(
    'key'    => '[your key]',
    'secret' => '[your secret]',
    'region' => '[aws-region]'
));

$adapter = new AwsS3Adapter($client, 'bucket-name', 'optional-prefix');

$filesystem = new Filesystem($adapter);
~~~
