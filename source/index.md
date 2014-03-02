---
layout: layout
---

# Flysystem

[![Build Status](https://travis-ci.org/thephpleague/flysystem.png)](https://travis-ci.org/thephpleague/flysystem)
[![Latest Stable Version](https://poser.pugx.org/league/flysystem/v/stable.png)](https://packagist.org/league//flysystem)
[![Total Downloads](https://poser.pugx.org/league/flysystem/downloads.png)](https://packagist.org/packages/league/flysystem)
[![Coverage Status](https://coveralls.io/repos/thephpleague/flysystem/badge.png)](https://coveralls.io/r/thephpleague/flysystem)
[![License](https://poser.pugx.org/league/flysystem/license.png)](https://packagist.org/packages/league/flysystem)

<ul class="quick_links">
    <li><a class="github" href="https://github.com/thephpleague/flysystem">View Source</a></li>
    <li><a class="twitter" href="https://twitter.com/frankdejonge">Follow Author</a></li>
</ul>

## What is Flysystem?

Flysystem is a filesystem abstraction which allows you to easily swap out a local filesystem for a remote one.

[Flysystem on Packagist](https://packagist.org/packages/league/flysystem)

## Goals

* Have a generic API for handling common tasks across multiple file storage engines.
* Have consistent output which you can rely on.
* Integrate well with other packages/frameworks.
* Be cacheable.
* Emulate directories in systems that support none, like AwsS3.
* Support third party plugins.
* Make it easy to test your filesystem interactions.
* Support streams for bigger file handling

## Bootstrap

~~~.language-php
use League\Flysystem\Filesystem;

$filesystem = new Filesystem($adapter);
~~~

To enable caching, pass a [cache adapter](/caching/) as the second constructor argument.

~~~.language-php
use League\Flysystem\Filesystem;

$filesystem = new Filesystem($adapter, $cache);
~~~

By default memory caching is used, which will cache result per request.

## Quick Example

~~~.language-php
// Create a file
$filesystem->write('path/to/file.txt', 'contents');

// Update a file
$filesystem->update('file/to/update.ext', 'new contents');

// Or delete a file
$filesyste->delete('delete/this/file.md');
~~~

These are just a couple of every day commands you'll run on any given filesystems.
Unfortunately the API for different kinds filesystems looks nothing alike. Flysystem
eliminates these differences by providing a normalized API layer. So when you're
switching from local storage to a cloud service, it's just a matter of configuration.

Flysystem eliminates for investing in hardware or cloud resources upfront and minimizes
the chances of vendor lock in. Develop locally and move to the cloud when your project
asks for it.
