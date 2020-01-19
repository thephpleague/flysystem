---
layout: default
title: Setup / Bootstrap
permalink: /v1/docs/usage/setup/
redirect_from:
    - /docs/usage/setup/
---

As explained in the [architecture description](/docs/architecture/), Flysystem uses
the _adapter pattern_. This means you'll always need an __adapter__, which needs to
be wrapped in a `Filesystem` instance. 

## Adapter Setup

Each adapter has their own setup and dependencies. For each adapter a setup guide is
provided. You can find the guides in the `adapters` section in the menu.

For this example we'll use the local adapter:

```php
<?php

use League\Flysystem\Adapter\Local;

$adapter = new Local(__DIR__.'/path/to/root/');
```

## Setup Filesystem

Now that you've got your adapter setup you can use it to create the filesystem:

```php
<?php

use League\Flysystem\Filesystem;

$filesystem = new Filesystem($adapter);
```

## Global Configuration

Adapters each have their own configuration. Apart from adapter constructors configuration
options can be provided in global configuration through the `Filesystem`.

```php
<?php

use League\Flysystem\Filesystem;

$filesystem = new Filesystem($adapter, ['visibility' => 'public']);
```

The global available configuration options are:

option | description | type
--- | --- | ---
`visibility` | default visibility | `string`
`disable_asserts` | disable extra calls to assert whether or not a file exists, see [Performance](/docs/advanced/performance/) | `bool`
`case_sensitive` | whether or not the adapter's file system is case sensitive, e.g. [Dropbox](/docs/adapter/dropbox/) is case insensitive | `bool`
