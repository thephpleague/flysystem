---
layout: default
title: Directory Listings
permalink: /v2/docs/usage/directory-listings/
---

Directory listings allow you to inspect the contents of a filesystem. The functionality
is provided by the `listContents` method. You can fetch _shallow_ and _deep_ listings by
specifying if the listing should be _recursive_ or not.

The _shallow_ listings will always provide you with every _file_ and _directory_ in the
listed path. The _deep_ listings **may** provide you with the directories, but will **always**
return all the files contained in the path.

## Backed by generators

The directory listings use generators to provide an efficient delivery mechanism for
storage items within the filesystems. This comes with one caveat; listings are a "read-once"
response. Unlike arrays, when a generator has yielded all of its response the items are gone.

## Filtering listing items

You can filter directory listings using the `filter` method.

```php
$allFiles = $filesystem->listContents('/some/path')
    ->filter(fn (StorageAttributes $attributes) => $attributes->isFile());
``` 

## Mapping listing items

You can transform directory listings using the `map` method.

```php
/** @var string[] $allPaths */
$allPaths = $filesystem->listContents('/some/path')
    ->filter(fn (StorageAttributes $attributes) => $attributes->isFile())
    ->map(fn (StorageAttributes $attributes) => $attributes->path())
    ->toArray();
```

## Sorting directory listings

In V1 directory listing responses were sorted, in V2 this is not the case by default. To sort
the listing, call the `sortByPath` method on the directory listing. Sorting directory listings will automatically
retrieve all the items as opposed to the default generator based responses, which are more memory performant.

```php
$sortedListing = $filesystem->listContents('/somewhere/over/the/rainbox')
    ->sortByPath()
    ->toArray();
```

## Storage attributes

Directory listings contain storage attributes, objects that expose information about
the items contained in a (part of a) filesystem.

There are two types of storage attributes:

- `League\Flysystem\DirectoryAttributes`
- `League\Flysystem\FileAttributes`

The common interface (`League\Flysystem\StorageAttributes`) provides some common information:

- `isDir(): bool`: to check whether the item is a directory
- `isFile(): bool`: to check whether the item is a file
- `path(): string`: to retrieve the location of the item

## ArrayAccess for attributes

The previous version of the directory listings returned arrays. These arrays were easy, but
didn't give any type-safety and required consumers to know a lot about the library's internal
structure. For V2, storage attribute classes are introduced. For legacy style access, these
classes implement the `ArrayAccess` interface, which allows you to fetch information from
the instances as if they are arrays.

For example:

```php
$lastModified = $fileAttributes->lastModified();
// is the same as
$lastModified = $fileAttributes['last_modified'];
```
