---
layout: default
permalink: /adapter/webdav/
title: WebDAV Adapter
---

# WebDAV Adapter

## Installation

~~~ bash
composer require league/flysystem-webdav
~~~

## Usage

~~~ php
$client = new Sabre\DAV\Client($settings);
$adapter = new League\Flysystem\WebDav\WebDavAdapter($client, $pathPrefix /* optional */);
$flysystem = new League\Flysystem\Filesystem($adapter);
~~~
