---
layout: default
title: Public URLs
permalink: /docs/usage/public-urls/
---

> Public URL generation was added in `3.6`

Flysystem can generate _public URLs_ for files. For adapters that can generate URLs by themselves, no additional
configuration is needed. For adapter that do not provide public URLs, a base URL can be configured in the main
Filesystem configuration.

```php
use League\Flysystem\Filesystem;

$filesystem = new Filesystem($adapter, ['public_url' => 'https://example.org/assets/']);
```

The following adapters have public URL generation capabilities:

- AWS S3
- Async AWS S3
- Google Cloud Storage
- WebDAV

## Usage

Public URLs can be generated using the `publicUrl` method.

```php
$publicUrl = $filesystem->publicUrl('path/to/file.txt');
```


