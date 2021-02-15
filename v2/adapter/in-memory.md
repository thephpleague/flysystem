---
layout: default
title: In Memory Filesystem Adapter
permalink: /v2/docs/adapter/in-memory/
---

Interacting with the local filesystem through Flysystem can be done
by using the `League\Flysystem\InMemory\InMemoryFilesystemAdapter`.

## Installation:

```bash
composer require league/flysystem-memory:^2.0
```

## Usage:

```php
// The internal adapter
$adapter = new League\Flysystem\InMemory\InMemoryFilesystemAdapter();

// The FilesystemOperator
$filesystem = new League\Flysystem\Filesystem($adapter);
```

