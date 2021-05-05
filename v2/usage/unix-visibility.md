---
layout: default
title: Unix-Style Visibility
permalink: /v2/docs/usage/unix-visibility/
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
