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

For the fallback, you can specify which hash algorithm to use.

```php
use League\Flysystem\Filesystem;

$filesystem = new Filesystem(
    $adapter,
    ['checksum_algo' => 'sha256']
);
```

You can use the `hash_algos` function to see [which algorithms](https://www.php.net/manual/en/function.hash-algos.php) are suppported for you.

## Usage

Checksums can be retrieved using the `checksum` method.

```php
$checksum = $filesystem->checksum('path/to/file.txt');

// for fallbacks, you can specify the algo during the method call too
$checksum = $filesystem->checksum('path/to/file.txt', ['checksum_algo' => 'sha1']);
```


