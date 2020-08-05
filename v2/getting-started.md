---
layout: default
title: Visibility
permalink: /v2/docs/getting-started/
---

## Installation

Flysystem can be installed using composer.

```bash
composer require league/flysystem
```

> NOTE: If you're installing a beta release, make sure to specify the
> exact version, otherwise you'll get a v1 release until v2 is fully released.

Additionally, you may want to install an extra adapter to interact with specific
types of filesystems. You can find the adapters in the menu.

## General usage

To safely interact with the filesystem, always wrap the adapter
in a `Filesystem` instance. You can read more about why in the
information about the [architecture](/v2/docs/architecture/).

```php
// SETUP
$adapter = new League\Flysystem\Local\LocalFilesystemAdapter($rootPath);
$filesystem = new League\Flysystem\Filesystem($adapter);

// USAGE
$filesystem->write($path, $contents);
```


