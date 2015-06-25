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

Visit [https://www.dropbox.com/developers/apps](https://www.dropbox.com/developers/apps) and get your "*App secret*".

You can also generate OAuth access token for testing using the Dropbox App Console without going through the authorization flow.

~~~ php
use League\Flysystem\Dropbox\DropboxAdapter;
use League\Flysystem\Filesystem;
use Dropbox\Client;

$client = new Client($accessToken, $appSecret);
$adapter = new DropboxAdapter($client, [$prefix]);

$filesystem = new Filesystem($adapter);
~~~
