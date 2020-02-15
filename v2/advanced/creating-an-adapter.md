---
layout: default
title: Create a Filesystem Adapter
permalink: /v2/docs/advanced/creating-an-adapter/
---

In case you have special requirements, or your filesystem of choice is
not available, you can always create your own adapter.

Every adapter must implement the `League\Flysystem\FilesystemAdapter`
interface. This interface defined all the required method and lists which
exceptions should be thrown in case of a failure.

## Testing your adapter

Testing your adapter is very important. For filesystem adapters, the best
tests are integration tests. This means the tests write to the _actual_
filesystems they are providing an interface to.

Although this is more time-consuming to run, this gives the most _real_
guarantees for the consumer of your package.

## Throwing exceptions

In order to see which exceptions need to be thrown, checkout the
[documentation about exceptions](/v2/docs/usage/exception-handling/).
