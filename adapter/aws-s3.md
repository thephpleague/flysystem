---
layout: default
permalink: /adapter/aws-s3/
title: Aws S3 Adapter
---

# Aws S3 Adapter

~~~ php
use Aws\S3\S3Client;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\AwsS3 as Adapter;

$client = S3Client::factory(array(
    'key'    => '[your key]',
    'secret' => '[your secret]',
));

$adapter = new Adapter($client, 'bucket-name', 'optional-prefix');
$filesystem = new Filesystem($adapter);
~~~
