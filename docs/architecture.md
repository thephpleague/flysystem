---
layout: default
title: Architecture
alternate_title: Architecture
permalink: /docs/architecture/
---

Flysystem uses the  **adapter pattern**. This pattern is especially
useful for mediating API incompatibilities, so it's a perfect fit
for our use-case. The `FilesystemInterface` represents the the outside
boundary, it defines how you should interact and integrate with
Flysystem. This is very important because the default implementation,
the `Filesystem` class, plays an important role in handling responses
from adapters.

## Adapter Responses

Adapter returns results in two forms: a success boolean _or_ a response
array. The response array contains several fields:

key         | description              | type
----------- | ------------------------ | -----------
type        | `file` or `dir`          | `string`
path        | path to the file or dir  | `string`
contents    | file contents            | `string`
stream      | file contents            | `resource`
visibility  | `public` or `private`    | `string`
timestamp   | modified time            | `integer`

In order to make the most out of every (expensive) filesystem call adapters
return as much information as they can when/if available. This makes it possible
for caching mechanisms to store this information so subsequent calls can be
returned from cache without the need for additional filesystem calls.
