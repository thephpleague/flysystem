---
layout: default
title: Create a Filesystem Adapter
permalink: /v2/docs/advanced/creating-an-adapter/
---

In case you have special requirements, or your filesystem of choice is
not available, you can always create your own adapter.

Every adapter must implement the `League\Flysystem\FilesystemAdapter`
interface. This interface defines all the required methods and lists which
exceptions should be thrown in case of a failure.

## Testing your adapter

Testing your adapter is very important. For filesystem adapters, the best
tests are integration tests. This means the tests write to the _actual_
filesystems they are providing an interface to.

Although this is more time-consuming to run, this gives the most _real_
guarantees for the consumer of your package.

There is a test package available that allows you to easily test your adapter:

```bash
composer require --dev league/flysystem-adapter-test-utilities
```

Once installed you can use the `League\Flysystem\AdapterTestUtilitiesFilesystemAdapterTestCase`
class as your adapter test base-class. This will ensure you're covering a lot of
test scenarios.

The test scenario tests your adapter as a black box, this means it's designed to perform
actual filesystem operations. All of the supported adapters perform actual interactions
with the underlying filesystems to provide the most accurate guarantees. 

## Throwing exceptions

In order to see which exceptions need to be thrown, checkout the
[documentation about exceptions](/v2/docs/usage/exception-handling/).
