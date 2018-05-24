---
layout: default
permalink: /docs/adapter/phpcr/
redirect_from: /adapter/phpcr/
title: PHPCR Adapter
---

This adapter works with any [PHPCR](http://phpcr.github.io) implementation.
Choose the one that fits your needs and add it to your project, or composer
will  complain that you miss `phpcr/phpcr-implementation`. See
[this article](http://symfony.com/doc/master/cmf/cookbook/database/choosing_phpcr_implementation.html)
for more on choosing your implementation.

## Installation

Assuming you go with jackalope-doctrine-dbal, do:

```bash
composer require jackalope/jackalope-doctrine-dbal league/flysystem-phpcr
```

## Usage

Bootstrap your PHPCR implementation. If you chose jackalope-doctrine-dbal with sqlite, 
this will look like this for example:

```php
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\DriverManager;
use Jackalope\RepositoryFactoryDoctrineDBAL;
use League\Flysystem\Filesystem;
use League\Flysystem\Phpcr\PhpcrAdapter;
use PHPCR\SimpleCredentials;

$connection = DriverManager::getConnection([
    'driver' => 'pdo_sqlite',
    'path'   => '/path/to/sqlite.db',
]);
$factory = new RepositoryFactoryDoctrineDBAL();
$repository = $factory->getRepository([
    'jackalope.doctrine_dbal_connection' => $connection,
]);
$session = $repository->login(new SimpleCredentials('username', 'password'));

//Or when no credentials are required
$session = $repository->login();

// this part looks the same regardless of your phpcr implementation.
$root = '/flysystem_tests';
$filesystem = new Filesystem(new PhpcrAdapter($session, $root));
```

### Indicate specific modification timestamp when writing content
By default PHPCR will use the current system time as the "last modified" timestamp of an entry when writing content. A specific timestamp can be provided by using the configuration array:

```php
$path = '/path/to/file.ext';
$content = file_get_contents($path);
$config = ['timestamp' => filemtime($path)]; //Use the time when the content of the file was last changed.

$filesystem->write($path, $content, $config);
```

This can be useful when the file timestamp needs to be preserved when copying a file structure to PHPCR.
