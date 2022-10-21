---
layout: default
title: Public URLs
permalink: /docs/usage/public-urls/
---

> Public URL generation was added in `3.6`

Flysystem can generate _public URLs_ for files. For adapters that can generate URLs by themselves, no additional
configuration is needed.

The following adapters have public URL generation capabilities:

- AWS S3
- Async AWS S3
- Azure Blob Storage
- Google Cloud Storage
- WebDAV

## Usage

Public URLs can be generated using the `publicUrl` method.

```php
$publicUrl = $filesystem->publicUrl('path/to/file.txt');
```

### Prefix public URL generation

For adapter that do not provide public URLs, a base URL can be configured in the main
Filesystem configuration.

```php
use League\Flysystem\Filesystem;

$filesystem = new Filesystem(
    $adapter,
    ['public_url' => 'https://example.org/assets/']
);
```

### Sharded URL generation

Most modern browsers allow 6 connections per domain. To circumvent this restriction, you can pass a list of
URL prefixes to distribute the paths across domains.

```php
use League\Flysystem\Filesystem;

$filesystem = new Filesystem(
    $adapter,
    [
        'public_url' => [
            'https://cdn1.example.org/',
            'https://cdn2.example.org/',
            'https://cdn3.example.org/',
        ]
    ]
);
```

The distribution mechanism is based on the following output `abs(crc32($path)) % count($prefixes)`. This produces
a reproducible distribution. This means that given the same list of prefixes, and the same path, the same URL is
produced.

### Custom public URL generator

```php
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;

$filesystem = new Filesystem(
    $adapter,
    publicUrlGenerator: new class() implements PublicUrlGenerator
    {
        public function publicUrl(string $path, Config $config): string
        {
            // implement your own public URL generation
        }
    }
);
```
```


