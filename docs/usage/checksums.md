---
layout: default
title: Checksums
permalink: /docs/usage/checksums/
---

> Checksum retrieval was added in `3.7`

Flysystem can resolve checksums for files. Some filesystems provide native checksum generation capabilities. As a
fallback, the Filesystem class can generate a polyfill checksum. In some cases, an Etag pseudo-checksum is provided.

The following adapters provide checksums:

- AWS S3 (etag)
- Async AWS S3 (etag)
- Google Cloud Storage (md5, crc32c, etag)
- Azure (md5)

By default, these implementations will return the first checksum type listed above. If native checksum generation
is not available, the filesystem will compute a checksum by (stream) reading the file and computing a hash. If the
chosen algorithm is not supported by your adapter, the fallback mechanism will be used to produce the checksum.

You can use the `hash_algos` function to see [which algorithms](https://www.php.net/manual/en/function.hash-algos.php)
are supported for your PHP version.

## Usage

Checksums can be retrieved using the `checksum` method.

```php
$checksum = $filesystem->checksum('path/to/file.txt'); // etag or md5

// you can specify the algo during the method call too
$checksum = $filesystem->checksum('path/to/file.txt', ['checksum_algo' => 'sha1']);
```

### Specifying a default checksum type

You can specify the default checksum type by configuring the `Filesystem` class.

```php
use League\Flysystem\Filesystem;

$filesystem = new Filesystem(
    $adapter,
    ['checksum_algo' => 'sha256']
);


$checksum = $filesystem->checksum('path/to/file.txt'); // always sha256
```


