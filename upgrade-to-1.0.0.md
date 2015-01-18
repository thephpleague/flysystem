---
layout: default
permalink: /upgrade-to-1.0.0/
title: Caching
---

# Upgrade to 1.0.0

While version 1.0.0 is largely backwards compatible from in every day usage,
some parts require a different boostrapping.

## Relocated Adapters

In order to have better dependency management, and to remove some of the
version contstraints, some of the adapters have been moved out or the main
repository. These adapters are:

* AwsS3: AWS SDK V2 Adapter - [docs](/adapter/aws-s3-v2/)
* AwsS3V3: AWS SDK V3 Adapter - [docs](/adapter/aws-s3-v3/)
* Dropbox: [docs](/adapter/dropbox/)
* Rackspace: [docs](/adapter/rackspace/)
* GridFS: [docs](/adapter/grid-fs/)
* Sftp: [docs](/adapter/sftp/)
* WebDAV: [docs](/adapter/webdav/)
* ZipArchive: [docs](/adapter/zip-archive/)

##  Caching

Caching has been removed from the main Filesystem class and is not implemented
as an adapter decorator.

### Version 0.x

~~~ php
$filesystem = new Filesystem($adapter, $cacheAdapter);
~~~

### Version 1.0.0

Install the required adapter decorator:

~~~ bash
composer require league/flysystem-cached-adapter
~~~

And convert the bootstrapping to:

~~~ php
use League\Flysystem\Adapter\Local;
use League\Flysystem\Cached\CachedAdapter;

$decoratedAdapter = new CachedAdapter($adapter, $cacheAdapter);
$filesystem = new Filesystem($decoratedAdapter);
~~~

## Helper Methods

In order to clean up the Filsystem class, some helper functions have been moved to plugins.

* ListWith
* ListPaths
* ListFiles
* GetWithMetadata
* EmptyDir (new)


