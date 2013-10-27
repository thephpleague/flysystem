# Flysystem

## Usage

```
<?php
use Flysystem\Filesystem;
use Flysystem\Adapter\AwsS3;
use Flysystem\Cache\Predis;

// Setup S3Client
$client = [...];
$adapter = new AwsS3($client, 'BucketName', 'prefix', [
	'ACL' => 'public-read',
]);
$filesystem = new Filesystem($adapter, $cache);

$filesystem->write('file.txt', 'file contents');
$filesystem->has('file.txt'); // true
$filesystem->rename('file.txt', 'new.txt');
$contents = $filesystem->read('new.txt'); // "file contents"
$listing = $filesystem->listContents();
$filesystem->delete('new.txt');
```