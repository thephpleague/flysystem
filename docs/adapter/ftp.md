---
layout: default
permalink: /docs/adapter/ftp/
redirect_from: /adapter/ftp/
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
]));
```
