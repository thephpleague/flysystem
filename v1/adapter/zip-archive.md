---
layout: default
permalink: /v1/docs/adapter/zip-archive/
redirect_from:
    - /docs/adapter/zip-archive/
    - /adapter/zip-archive/
title: ZipArchive Adapter
---

## Installation

```bash
composer require league/flysystem-ziparchive:^1.0
```

## Usage

```php
use League\Flysystem\Filesystem;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;

$filesystem = new Filesystem(new ZipArchiveAdapter(__DIR__.'/path/to/archive.zip'));
```

### Force Save

When creating a new zip file it will only be saved at the end of the PHP request because the ZipArchive library relies on an internal `__destruct` method to be called. You can force the saving of the zip file before the end of the request by calling the `close` method on the archive through the adapter.

```php
$filesystem->getAdapter()->getArchive()->close();
```
