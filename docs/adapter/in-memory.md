---
layout: default
title: In Memory Filesystem Adapter
permalink: /docs/adapter/in-memory/
redirect_from:
    - /v2/docs/adapter/in-memory/
    - /docs/adapter/memory/
---

This adapter keeps the filesystem completely in memory. This is useful
when you need a filesystem, but don’t want it persisted. It can be done
by using the `League\Flysystem\InMemory\InMemoryFilesystemAdapter`.

This adapter can be used in tests as a test double and removes much of
the need to mock your Flysystem dependency.

## Installation:

```bash
composer require league/flysystem-memory:^3.0
```

## Usage:

```php
// The internal adapter
$adapter = new League\Flysystem\InMemory\InMemoryFilesystemAdapter();

// The FilesystemOperator
$filesystem = new League\Flysystem\Filesystem($adapter);
```

