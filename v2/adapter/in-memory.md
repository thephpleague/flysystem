---
layout: default
title: Architecture
permalink: /v2/docs/adapter/in-memory/
---

Interacting with the local filesystem through Flysystem can be done
by using the `League\Flysystem\InMemory\InMemoryFilesystemAdapter`.

### Simple usage:

```php
// The internal adapter
$adapter = new League\Flysystem\InMemory\InMemoryFilesystemAdapter();

// The FilesystemOperator
$filesystem = new League\Flysystem\Filesystem($adapter);
```

