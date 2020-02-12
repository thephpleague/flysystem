---
layout: default
title: Architecture
permalink: /v2/docs/adapter/local/
---

Interacting with the local filesystem through Flysystem can be done
by using the `League\Flysystem\Local\LocalFilesystemAdapter`.

### Simple usage:

```php
// The internal adapter
$adapter = new League\Flysystem\Local\LocalFilesystem(
    // Determine root directory
    __DIR__.'/root/directory/'
);

// The FilesystemOperator
$filesystem = new League\Flysystem\Filesystem($adapter);
```

### Advanced usage:

```php
use League\Flysystem\Local\LocalFilesystem;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;

// The internal adapter
$adapter = new LocalFilesystem(
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
    LocalFilesystem::DISALLOW_LINKS
);

// The FilesystemOperator
$filesystem = new League\Flysystem\Filesystem($adapter);
```

If you want to learn more about the permissions for local adapters,
read the [docs about unix visibility](/v2/docs/usage/unix-visibility/) 

