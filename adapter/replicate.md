---
layout: default
permalink: /adapter/replicate/
title: Replicate Adapter
---

# Replicate Adapter

The `ReplicateAdapter` facilitates smooth transitions between adapters, allowing an application to stay functional and migrate its files from one adapter to the other. The adapter takes two other adapters, a source and a replica. Every change is delegated to both adapters, while all the read operations are passed onto the source only.

~~~ php
$source = new League\Flysystem\Adapter\AwsS3(...);
$replica = new League\Flysystem\Adapter\Local(...);
$adapter = new League\Flysystem\Adapter\ReplicateAdapter($source, $replica);
~~~
