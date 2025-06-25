---
layout: default
title: Getting Started
permalink: /docs/getting-started/
redirect_from: /v2/docs/getting-started/
---

## Installation

Flysystem can be installed using composer.

```bash
composer require league/flysystem:^3.0
```

## General usage

To safely interact with the filesystem, always wrap the adapter
in a `Filesystem` instance. You can read more about why in the
information about the [architecture](/docs/architecture/).

## Example Usage

This example code snippet creates a File using the [Local Filesystem Adapter](/docs/adapter/local). Additionally, you may want to install an extra adapter to interact with specific types of filesystems. You can find the adapters in the menu.

```php
// Create a Local Filesystem adapter
$adapter = new \League\Flysystem\Local\LocalFilesystemAdapter(__DIR__ . '/storage');

// Create a new Filesystem instance with that adapter
$filesystem = new \League\Flysystem\Filesystem($adapter);

// Create a file "Example.txt" with the contents "Lorem ipsum dolor sit amet."
$filesystem->write(location: 'Example.txt', contents: 'Lorem ipsum dolor sit amet.');
```
