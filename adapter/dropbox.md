---
layout: default
permalink: /adapter/dropbox/
title: Dropbox Adapter
---

# Dropbox Adapter

## Installation

~~~ bash
composer require league/flysystem-dropbox
~~~

## Usage

~~~ php
use League\Flysystem\Dropbox\DropboxAdapter;
use League\Flysystem\Filesystem;
use Dropbox\Client;

$client = new Client(/* CREDENTIALS */);
$adapter = new DropboxAdapter($client, [$prefix]);

$filesystem = new Filesystem($adapter);
~~~
