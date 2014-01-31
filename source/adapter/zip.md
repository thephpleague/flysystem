---
layout: layout
title: Zip Adapter
---

# Zip Adapter

~~~.language-php
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Zip as Adapter;

$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/archive.zip'));
~~~
