---
layout: default
title: Local Filesystem Adapter
permalink: /v2/docs/adapter/local/
---

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

    // How to deal with links, either DISALLOW_LINKS or SKIP_LINKS
    // Disallowing them causes exceptions when encountered
    LocalFilesystemAdapter::DISALLOW_LINKS
);

// The FilesystemOperator
$filesystem = new League\Flysystem\Filesystem($adapter);
```

### Visibility Converter

If you want to learn more about the permissions for local adapters,
read the [docs about unix visibility](/v2/docs/usage/unix-visibility/) 

