---
layout: default
title: Upgrade to 2.0.0
permalink: /v2/docs/advanced/upgrade-to-2.0.0/
---

Flysystem V1 was released on the 19th of January in 2015. Since then it has maintained full
backwards compatibility. In order to guarantee that for the years to come, some changes were
needed. Some functionality needed to be standardized in order to make behavior more
predictable. This meant some functionality needed to be scoped down, some "accidental" features
were removed.

### Upgrade your dependencies

Firstly you'll need to upgrade Flysystem itself. You can do this by requiring `^2.0`
instead of  `^1.0`. The same needs to be done for all the adapters too, in the same action.

## Removed functionality.

### Filesystem::getMetadata is removed

The `getMetadata` method is removed, in favour of better metadata getters.

### Plugins are removed

The plugin functionality was a flawed concept from the start. It promoted bad practices
and didn't help in terms of predictability and the design of consuming code. With the
improved guarantees of what methods do, some plugins became redundant because they
augmented broken behavior in V1.

Apart from that, many cases for plugins should have just been consumer code.

Plugin | Alternative
--- | ---
`EmptyDir` | Just remove the directory, they're always created implicitly.
`ForcedCopy` | This is no longer needed since `copy` if always forced.
`ForcedRename` | This is no longer needed since `move` is now always forced.
`GetWithMetadata` | Use specific metadata getters instead.
`ListFiles` | Filter on the contents listing instead.
`ListPaths` | Transform over the contents listing instead.
`ListWith` | This operation is very unpredictable and has bad performance, don't use it.

## Changes

### Rename is now move, specific for files.

When this method was introduced, the `rename` operation didn't move files to new parent
directory. This behavior was added later. Renaming the operation `move` better reflects
this behaviour. It will now also enforce that it _only moves files_, this is the
only way to be adapter agnostic, which is the main purpose of the library.

```diff
- $filesystem->rename($path); 
+ $filesystem->move($path);
```

### No arbitrary abbreviations

No need for beep-boop language (computer-speak).

```diff
- $filesystem->createDir($path);
+ $filesystem->createDirectory($path);
```

### Writes are now deterministic

No more `update`, `updateStream`, `put`, and `putStream`. You can simply use `write` and `writeStream`.

```diff
- $filesystem->update($path, $contents);
+ $filesystem->write($path, $contents);

- $filesystem->updateStream($path, $contents);
+ $filesystem->writeStream($path, $contents);

- $filesystem->put($path, $contents);
+ $filesystem->write($path, $contents);

- $filesystem->putStream($path, $contents);
+ $filesystem->writeStream($path, $contents);
```


### Metadata getters are renamed:

With a new major version, BC breaks are allowed. After much consideration, this was _the_ moment
to rename functions that had a sub-optimal name. Since Flysystem V2 will probably be supported as
long as V1 is, breaking with past mistakes is needed sometimes.

```diff
// Explains better how to interpret the response
- $lastModified = $filesystem->getTimestamp($path); 
+ $lastModified = $filesystem->lastModified($path);

// More explicit it's only for files, which is adapter agnostic.
- $fileExists = $filesystem->has($path); 
+ $fileExists = $filesystem->fileExists($path);

// Correct casing.
- $mimetype = $filesystem->getMimetype($path); 
+ $mimetype = $filesystem->mimeType($path);

// More explicit about what type of size
- $fileSize = $filesystem->getSize($path); 
+ $fileSize = $filesystem->fileSize($path);

// In line with other metadata methods.
- $visibility = $filesystem->getVisibility($path); 
+ $visibility = $filesystem->visibility($path);
```

## Directory Listings

Directory listings received a big overhaul. To read more about it check out the
[documentation for directory listings](/v2/docs/usage/directory-listings/).

## Miscellaneous changes

- All adapters have changed constructors to allow more modular extension points.
- The cached adapter was not ported to V2.
