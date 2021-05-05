---
layout: default
title: Design Considerations
permalink: /v2/docs/design-considerations/
---

As is true for any abstraction, Flysystem is a 80-20 solution. This
means it does 80% of the things very well, for the special other 20%
it's better not to use Flysystem. In this document you can see what
those trade-offs are and why they are made.

> Did you spot an inconsistency when using Flysystem? Create an issue
> on the [flysystem repository](https://github.com/thephpleague/flysystem)
> to discuss whether or not this was a missing design consideration.

## Directories are only created when needed

In the world of cloud storage, directories are not required. In some
cases, they are even implemented as an afterthought. These filesystems
act more like key-value stores. This causes Flysystem to have to make
a choice. Either every cloud storage will have to create directories,
or directories do not _have to be created_ for any of them. Flysystem
chose to do the latter. Directories are second grade citizens within
the package.

This means a couple of things:

- Directories _may_ be created when writing a file to a nested path.
- Directories _may_ be returned in recursive directory listings.
- Directories _may_ not be created, even when calling createDirectory.

Most adapters allow you to create directories. For others, they simply
do not exist. Any type of Filesystem that's commonly used to display
files to users (Local/S3/etc) all have directory creation support. Each
adapter that doesn't will have to list that in their respective
documentation.

## Directory listings are backed by generators

Flysystem is being used in some applications that deal with a very large
amount of files. In these cases, we need a solution that limits the amount
of memory it takes to list all of them. Generators are perfect for that, as
they remove the need to collect all the files at once.

The response from a `listContents` call returns a
`League\Flysystem\DirectoryListing` instance, which adds some convenience
methods over the raw `Generator` result.

