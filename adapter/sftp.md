---
layout: default
permalink: /adapter/sftp/
title: SFTP Adapter
---

# SFTP Adapter

~~~ php
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Sftp as Adapter;

$filesystem = new Filesystem(new Adapter(array(
    'host' => 'example.com',
    'port' => 21,
    'username' => 'username',
    'password' => 'password',
    'privateKey' => 'path/to/or/contents/of/privatekey',
    'root' => '/path/to/root',
    'timeout' => 10,
)));
~~~
