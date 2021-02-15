---
layout: default
permalink: /v1/docs/adapter/sftp/
redirect_from: 
    - /docs/adapter/sftp/
    - /adapter/sftp/
title: SFTP Adapter
---

## Installation

```bash
composer require league/flysystem-sftp:^1.0
```

## Usage

```php
use League\Flysystem\Filesystem;
use League\Flysystem\Sftp\SftpAdapter;

$filesystem = new Filesystem(new SftpAdapter([
    'host' => 'example.com',
    'port' => 22,
    'username' => 'username',
    'password' => 'password',
    'privateKey' => 'path/to/or/contents/of/privatekey',
    'root' => '/path/to/root',
    'timeout' => 10,
]));
```
