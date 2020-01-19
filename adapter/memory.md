---
layout: default
permalink: /docs/adapter/memory/
redirect_from: /adapter/memory/
title: Memory Adapter
---

This adapter keeps the filesystem completely in memory. This is useful when you need a filesystem, but don't want it persisted.

## Installation

```bash
composer require league/flysystem-memory
```

## Usage

```php
use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;

$filesystem = new Filesystem(new MemoryAdapter());
```
