---
layout: default
permalink: /adapter/webdav/
title: WebDAV Adapter
---

# WebDAV Adapter

~~~ php
$client = new Sabre\DAV\Client($settings);
$adapter = new League\Flysystem\Adapter\WebDav($client, $pathPrefix /* optional */);
$flysystem = new League\Flysystem\Filesystem($adapter);
~~~
