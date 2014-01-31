---
layout: layout
title: Local Adapter
---

# Local Adapter

~~~.language-php
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local as Adapter;

$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/root'));
~~~
