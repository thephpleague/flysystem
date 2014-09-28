---
layout: default
permalink: /adapter/zip-archive/
title: ZipArchive Adapter
---

# ZipAchive Adapter

The `ReplicateAdapter` enabled smooth transition between adapters, allowing a application to stay functional and migrate it's files from one adapter to the other. The adapter takes two other adapters, a source and a replica. Every change is delegated to both adapters, while all the read operations are passed onto the source only.

~~~ php
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Zip as Adapter;

$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/archive.zip'));
~~~
