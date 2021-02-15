---
layout: default
title: SFTP Adapter
permalink: /v2/docs/adapter/sftp/
---

## Installation

```bash
composer require league/flysystem-sftp:^2.0
```

## About

## Setup

```php
use League\Flysystem\Filesystem;
use League\Flysystem\PhpseclibV2\SftpConnectionProvider;
use League\Flysystem\PhpseclibV2\SftpAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;


$filesystem = new Filesystem(new SftpAdapter(
    new SftpConnectionProvider(
        'localhost', // host (required)
        'foo', // username (required)
        'pass', // password (optional, default: null) set to null if privateKey is used
        '/path/to/my/private_key', // private key (optional, default: null) can be used instead of password, set to null if password is set
        'my-super-secret-passphrase-for-the-private-key', // passphrase (optional, default: null), set to null if privateKey is not used or has no passphrase
        2222, // port (optional, default: 22)
        true, // use agent (optional, default: false)
        30, // timeout (optional, default: 10)
        10, // max tries (optional, default: 4)
        'fingerprint-string', // host fingerprint (optional, default: null),
        null, // connectivity checker (must be an implementation of 'League\Flysystem\PhpseclibV2\ConnectivityChecker' to check if a connection can be established (optional, omit if you don't need some special handling for setting reliable connections)
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
