---
layout: default
title: Path Prefixing Adapter Decorator
permalink: /docs/adapter/path-prefixing/
---

Any filesystem adapter can be scoped down to a prefixed path
using the `League\Flysystem\PathPrefixing\PathPrefixedAdapter`.

## Installation:

```bash
composer require league/flysystem-path-prefixing:^3.3
```

## Usage:

```php
// The internal adapter, any
$adapter = new League\Flysystem\InMemory\InMemoryFilesystemAdapter();

// Turn it into a path-prefixed adapter
$adapter = new League\Flysystem\PathPrefixng\PathPrefixedAdapter($adapter, 'a/path/prefix');

// Instantiate the filesystem
$filesystem = new League\Flysystem\Filesystem($adapter);
```

