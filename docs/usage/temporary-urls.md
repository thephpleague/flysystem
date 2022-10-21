---
layout: default
title: Temporary URLs
permalink: /docs/usage/temporary-urls/
---

> Temporary URL generation was added in `3.10`

Flysystem can generate _temporary URLs_ for files. Temporary URL provide access to files that may otherwise
not be accessible directly by URL. These URLs expire after a given point in time, after which the URL becomes
un-usable.

The following adapters have temporary URL generation capabilities:

- AWS S3
- Async AWS S3
- Azure Blob Storage
- Google Cloud Storage

## Usage

```php
$temporaryUrl = $filesystem->temporaryUrl('path/to/file.txt', $dateTimeOfExpiry);
```

You can override the temporary URL generation process by providing a `TemporaryUrlGenerator` instance to the
`Filesystem` constructor.

```php
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;

$filesystem = new Filesystem(
    $adapter
    temporaryUrlGenerator: new class() implements TemporaryUrlGenerator
    {
        public function temporaryUrl(
            string $path,
            DateTimeInterface $expiresAt,
            Config $config
        ): string {
            // implement your own temporary URL generation strategy
        }
    }
);
```


