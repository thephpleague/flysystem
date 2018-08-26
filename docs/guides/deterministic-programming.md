---
layout: default
title: Deterministic Programming
permalink: /docs/guides/deterministic-programming/
---

Deterministic programming is a great way to approach handling filesystems
in PHP. Deterministic means that given a certain input you'll always get
the same output. When we apply this approach to handling filesystems we're
able to mitigate many of the things that make filesystem handling
problematic.

## Filesystems are slow.

In general, filesystem interaction is slow. Every operation that
hits the disc in one way or another is slow. While some operations absolutely
require filesystem interaction, there's a number of cases where filesystem
operations can be prevented in order to eliminate the associated penalties.

## Caching metadata.

Whenever we store files uploaded by the user it's good to immediately store
any associated metadata with them if you'll need to use that later on. By storing
the metadata we've limited the expense of the filesystem call to a single time.
All subsequent calls can use the cached value. This is particularly effective
because most files don't change after they're uploaded.

## Deterministic file locations.

File storage organization can be a tricky thing. Much like "naming things" this
task often isn't as trivial as it may seem. Creating a naming scheme for your
files can help a lot. For example:

```text
/users/profile_pictures/{user_id}_{size}.png
/blog/posts/{post_id}_header.png
/blog/posts/{post_id}_preview.png
```

Once you've got the pattern, you can formalize this in a class:

```php
<?php

class FilePathGenerator
{
    public function userProfilePicture($userId, $size): string
    {
        return "/users/profile_pictures/{$userId}_{$size}.png";
    }
}
```

The generated path can be used as the storage, but it can also
be used as a deterministic lookup mechanism. Instead of guessing
where a file might be, you'll now know for sure.

By doing this you'll prevent searches for files and limit it to
a single `file_exists` check if you need to be sure the file exists.
For Flysystem this would be a `$filesystem->has($path);` call.

## Storing all file locations.

In order to get rid of almost all the penalties caused by filesystems
you can store all the paths in a persistent database. While this is
not a conventional thing to do it certainly has a lot of benefits.

File listings are just a select statement:  `SELECT * FROM files WHERE path LIKE '/prefix/%'`.
Listings can easily be sorted. File existence checks are inexpensive.
File migrations become really easy, even when your path generation
strategy changes. Metadata can be stored alongside your path.
