---
layout: default
title: What is new in Flysystem V2
permalink: /v2/docs/what-is-new/
---

Flysystem V2 is a full, from the ground up rewrite. It features simplifications
of the interface, introduces a new error/exception handling strategy, and
brings all the latest type-related features of PHP 7 to the filesystem.

While the overall concept of Flysystem has not changed, V2 brings you a bunch
of developer-experience improvements. Let's check them out!

## API Simplification

### No more `update`, `updateStream`, `put`, and `putStream`

Previously, when writing and updating files, specific methods needed to be called
for either situation. In V2, both cases are covered by the `write` and `writeStream`
methods. This means, the write methods will overwrite any previously written file.
The `update` and `updateStream` methods are no longer required and have been removed
from the main interface.

The `put` method was exposed to prevent having to choose between `write`
and `update`. Needless to say, this method now has no value and has been removed.

In addition to a more streamlined API, each write call is now less expensive. Since
writes now overwrite, there is no longer a file existence check needed. For all the
"over the network filesystems", this is a big win!  

### No more success result booleans

In V1, in order to see if your filesystem operation was a success, you had to assign
the result to a variable and check if it was true. This sometimes made the DX a little
quirky. For example:

```php
try {
    $success = $filesystem->write('path.txt', 'contents');

    if ($success) {
        // it is ok!
    } else {
        // it failed!
    }
} catch (Throwable $exception) {
    // it failed! (in a different way)
}
```

As you can see in the example above, in order to handle all errors you need to do so
in two places. This is a bit annoying, so in V2 all error cases result in exceptions!

The same thing in V2 is simply:

```php
try {
    $filesystem->write('path.txt', 'contents');
    // it is ok!
} catch (FilesystemException $exception) {
    // it failed!
}
```

This makes the usage a lot simpler, and more simple is more better!

## Error handling with exceptions.

With all errors resulting in exceptions, the need for a streamlined approach
for exception handling appeared. There was little consistency between the various
exceptions thrown in V1. For V2, the exceptions have been planned out carefully.

For an in-depth overview of how it all works in V2, read about it in
the [docs about exception handling](/v2/docs/usage/exception-handling/).

## Better content listing developer experience

Developer experience was top of mind when creating V2 of Flysystem. Well-known
issues were tackled. One of these was the response for a `listContents` call.

You can read more about it in the
[docs about directory listings](/v2/docs/usage/directory-listings/).

## Custom mime-type detection

In V1, looking up mime-types could give performance penalties. In V2, this
component was extracted into its own package called `league/mime-type-detection`.
This package allows you to control how a mime-type is resolved for a
path + file contents combination. This package is shipped by default with
Flysystem.

## Customizable visibility conversion

All adapters now provide their own interface to convert visibility
input and configuration options to their implementation specific
permissions. This gives you fine-grained control over your
security settings.

## Replaceable path normalizations

In V1, the filesystem protected against path traversals and weird whitespace in
paths. For V2, this was extracted into its own internal component, allowing you
to replace this behavior entirely, or add your own special verification on top
of the traversal protection. 

## Plugins are removed

In V1, plugins allowed you to extend the functionality of the filesystem. It used a
lot of magic to accomplish this, which creates an unpredictable API and promotes
bad object-oriented design. If you need additional filesystem functionality, simply
create the functionality outside of Flysystem and use it.
