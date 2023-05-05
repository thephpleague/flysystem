---
layout: default
permalink: /docs/adapter/zip-archive/
title: ZipArchive Adapter
---

## Installation

```bash
composer require league/flysystem-ziparchive
```

## Usage

```php
use League\Flysystem\Filesystem;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use League\Flysystem\ZipArchive\FilesystemZipArchiveProvider;

$adapter = new ZipArchiveAdapter(
    new FilesystemZipArchiveProvider($pathToZip)
);
$filesystem = new Filesystem($adapter);
```
