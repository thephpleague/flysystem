---
layout: default
title: Unix-Style Visibility
permalink: /docs/usage/unix-visibility/
redirect_from: /v2/docs/usage/unix-visibility/
---

For a number of adapters, the visibility settings are based on unix-style
permissions. Since every one of these are the same, a general implementation
is provided in Flysystem.

At the base of this module is the
`League\Flysystem\UnixVisibility\VisibilityConverter` interface. This
interface is implemented by the
`League\Flysystem\UnixVisibility\PortableVisibilityConverter` interface. Every adapter
provided by Flysystem uses a standard way of specifying `public` and `private`
visibility options. This allows you to have portability between adapters.
However, if your needs require something more specific, this interface allows
you to implement something that makes sense for your case.

## Specifying your own portable visibility

```php
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;

$visibilityConverter = PortableVisibilityConverter::fromArray([
    'file' => [
        'public' => 0640,
        'private' => 0604,
    ],
    'dir' => [
        'public' => 0740,
        'private' => 7604,
    ],
]);
```

## Visibility Disclaimer

Flysystem's visibility conversion is based on [POSIX file permissions](https://en.wikipedia.org/wiki/File_system_permissions).
It uses hard comparison (`===`) to determine if a file is public or private and falls back when there is no match. This
default means we cannot say a file is private. Checking the visibility of a file should NOT be used as an indication the
file is fully public or fully private. It should also not be used to signal intent of wether or not the file is allowed
to be accessed.
