---
layout: default
title: Read-only Adapter Decorator
permalink: /docs/adapter/read-only/
---

Any filesystem adapter can be made read-only by decorating them
using the `League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter`.

## Installation:

```bash
composer require league/flysystem-read-only:^3.3
```

## Usage:

```php
// The internal adapter, any
$adapter = new League\Flysystem\InMemory\InMemoryFilesystemAdapter();

// Turn it into a read-only adapter
$adapter = new League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter($adapter);

// Instantiate the filesystem
$filesystem = new League\Flysystem\Filesystem($adapter);
```

