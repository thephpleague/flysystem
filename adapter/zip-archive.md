---
layout: default
permalink: /adapter/zip-archive/
title: ZipArchive Adapter
---

# ZipArchive Adapter

## Installation

~~~ bash
composer require league/flysystem-ziparchive
~~~

## Usage

~~~ php
use League\Flysystem\Filesystem;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;

$filesystem = new Filesystem(new ZipArchiveAdapter(__DIR__.'/path/to/archive.zip'));
~~~
