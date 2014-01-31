---
layout: layout
title: Installation
---

Installation
============

This library is available via Composer:

~~~.language-javascript
{
    "require": {
        "league/flysystem": "0.2.*"
    }
}
~~~

You can also use Flysystem without using Composer by registing an autoloader function:

~~~.language-php
spl_autoload_register(function($class) {
    if ( ! substr($class, 0, 17) === 'League\\Flysystem') {
        return;
    }

    $location = __DIR__ . 'path/to/league/flysystem/src/' . str_replace('\\', '/', $class) . '.php';

    if (is_file($location)) {
        require_once($location);
    }
});
~~~