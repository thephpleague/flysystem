---
layout: layout
title: WebDAV Adapter
---

# WebDAV Adapter

This package uses the [SabreDAV](https://packagist.org/packages/sabre/dav) package.

~~~.language-php
$client = new Sabre\DAV\Client($settings);
$adapter = new League\Flysystem\Adapter\WebDav($client);
$flysystem = new League\Flysystem\Filesystem($adapter);
~~~
