---
layout: default
permalink: /installation/
title: Installation
---

# Installation

Through Composer, obviously:

~~~ json
{
    "require": {
        "league/flysystem": "0.5.*"
    }
}
~~~

You can also use Flysystem without using Composer by registering an autoloader function:

~~~ php
spl_autoload_register(function($class) {
    $prefix = 'League\\Flysystem\\';

    if ( ! substr($class, 0, 17) === $prefix) {
        return;
    }

    $class = substr($class, strlen($prefix));
    $location = __DIR__ . 'path/to/flysystem/src/' . str_replace('\\', '/', $class) . '.php';

    if (is_file($location)) {
        require_once($location);
    }
});
~~~