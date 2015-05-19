---
layout: default
permalink: /adapter/local/
title: Local Adapter
---

# Local Adapter

## Installation

Comes with the main Flysystem package.

## Usage

~~~ php
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local as Adapter;

$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/root'));
~~~
