---
layout: default
permalink: /adapter/null-test/
title: Null Adapter
---

# Null Adapter

Acts like /dev/null

~~~ php
$adapter = new League\Flysystem\Adapter\NullAdapter;
$flysystem = new League\Flysystem\Filesystem($adapter);
~~~
