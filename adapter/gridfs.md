---
layout: default
permalink: /adapter/gridfs/
title: FTP Adapter
---

# GridFS Adapter

~~~ php
use League\Flysystem\GridFS\GridFSAdapter;
use League\Flysystem\Filesystem;

$mongoClient = new MongoClient();
$gridFs = $mongoClient->selectDB('db_name')->getGridFS();

$adapter = new GridFSAdapter($gridFs);
$filesystem = new Filesystem($adapter);
~~~
