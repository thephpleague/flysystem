---
layout: default
title: Local Filesystem Adapter
permalink: /docs/adapter/local/
redirect_from: /v2/docs/adapter/local/
---

## Installation

This adapter is shipped with the main package.

```bash
composer require league/flysystem:^3.0
```

Interacting with the local filesystem through Flysystem can be done
by using the `League\Flysystem\Local\LocalFilesystemAdapter`.

## Simple usage:

```php
// The internal adapter
$adapter = new League\Flysystem\Local\LocalFilesystemAdapter(
    // Determine root directory
    __DIR__.'/root/directory/'
);

// The FilesystemOperator
$filesystem = new League\Flysystem\Filesystem($adapter);
```

## Advanced usage:

```php
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;

// The internal adapter
$adapter = new LocalFilesystemAdapter(
    // Determine the root directory
    __DIR__.'/root/directory/',

    // Customize how visibility is converted to unix permissions
    PortableVisibilityConverter::fromArray([
        'file' => [
            'public' => 0640,
            'private' => 0604,
        ],
        'dir' => [
            'public' => 0740,
            'private' => 7604,
        ],
    ]),

    // Write flags
    LOCK_EX,

    // How to deal with links when listing directory contents, either DISALLOW_LINKS or SKIP_LINKS
    // Disallowing them causes exceptions when encountered
    LocalFilesystemAdapter::DISALLOW_LINKS
);

// The FilesystemOperator
$filesystem = new League\Flysystem\Filesystem($adapter);
```

### Visibility Converter

If you want to learn more about the permissions for local adapters,
read the [docs about unix visibility](/docs/usage/unix-visibility/) 

### Symlink Treatment

Symlinks are a concept that are not supported by every adapter, that is why when a directory is listed symlinks have
special treatment. By default, when they are encountered during listing, an exception is thrown. You can optionally
configure the adapter to skip them during listing by passing LocalFilesystemAdapter::SKIP_LINKS in the constructor for
the `$linkHandling` constructor parameter.

When **reading** files, symlinks are treated in the same way PHP treats them, their content is read. No exception is
thrown when link handling is configured to disallow links. The only difference is directory listing behaviour.

