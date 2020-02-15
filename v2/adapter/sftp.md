---
layout: default
title: SFTP Adapter
permalink: /v2/docs/adapter/sftp/
---

## Setup

```php
use League\Flysystem\Filesystem;
use League\Flysystem\PHPSecLibV2\SftpConnectionProvider;
use League\Flysystem\PHPSecLibV2\SftpAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;


$filesystem = new Filesystem(new SftpAdapter(
    new SftpConnectionProvider(
        'localhost', // host (required)
        'foo', // username (required)
        'pass', // password (required)
        2222, // port (optional, default: 22)
        true, // use agent (optional, default: false)
        30 // timeout (optional, default: 10)
    ),
    '/upload', // root path (required)
    PortableVisibilityConverter::fromArray([
        'file' => [
            'public' => 0640,
            'private' => 0604,
        ],
        'dir' => [
            'public' => 0740,
            'private' => 7604,
        ],
    ])
));
```
