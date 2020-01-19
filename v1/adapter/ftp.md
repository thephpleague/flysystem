---
layout: default
permalink: /v1/docs/adapter/ftp/
redirect_from:
    - /docs/adapter/ftp/
    - /adapter/ftp/
title: FTP Adapter
---

This adapter ships with Flysystem by default.

## Usage

```php
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Ftp as Adapter;

$filesystem = new Filesystem(new Adapter([
    'host' => 'ftp.example.com',
    'username' => 'username',
    'password' => 'password',

    /** optional config settings */
    'port' => 21,
    'root' => '/path/to/root',
    'passive' => true,
    'ssl' => true,
    'timeout' => 30,
    'ignorePassiveAddress' => false,
]));
```
