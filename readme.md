# Flysystem by [@frankdejonge](http://twitter.com/frankdejonge)

[![Build Status](https://travis-ci.org/thephpleague/flysystem.png)](https://travis-ci.org/thephpleague/flysystem)
[![Latest Stable Version](https://poser.pugx.org/league/flysystem/v/stable.png)](https://packagist.org/packages/league/flysystem)
[![Total Downloads](https://poser.pugx.org/league/flysystem/downloads.png)](https://packagist.org/packages/league/flysystem)
[![Coverage Status](https://coveralls.io/repos/thephpleague/flysystem/badge.png)](https://coveralls.io/r/thephpleague/flysystem)
[![License](https://poser.pugx.org/league/flysystem/license.png)](https://packagist.org/packages/league/flysystem)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/9820f1af-2fd0-4ab6-b42a-03e0c821e0af/mini.png)](https://insight.sensiolabs.com/projects/9820f1af-2fd0-4ab6-b42a-03e0c821e0af)

Flysystem is a filesystem abstraction which allows you to easily swap out a local filesystem for a remote one.

[Flysystem on Packagist](https://packagist.org/packages/league/flysystem)

# Goals

* Have a generic API for handling common tasks across multiple file storage engines.
* Have consistent output which you can rely on.
* Integrate well with other packages/frameworks.
* Be cacheable.
* Emulate directories in systems that support none, like AwsS3.
* Support third party plugins.
* Make it easy to test your filesystem interactions.
* Support streams for bigger file handling

# Installation

Through Composer, obviously:

```json
{
    "require": {
        "league/flysystem": "0.2.*"
    }
}
```

You can also use Flysystem without using Composer by registering an autoloader function:

```php
spl_autoload_register(function($class) {
    if (!substr($class, 0, 17) === 'League\\Flysystem') {
        return;
    }

    $location = __DIR__ . 'path/to/flysystem/src/' . str_replace('\\', '/', $class) . '.php';

    if (is_file($location)) {
        require_once($location);
    }
});
```

## Adapters

* Local
* Amazon Web Services - S3
* Rackspace Cloud Files
* Dropbox
* Ftp
* Sftp (through phpseclib)
* Zip (through ZipArchive)
* WebDAV (through SabreDAV)

### Planned Adapters

* Azure
* PR's welcome?

## Caching

* Memory (array caching)
* Redis (through Predis)
* Memcached
* Adapter

## Local Setup

```php
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local as Adapter;

$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/root'));
```

## Zip Archive Setup

```php
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Zip as Adapter;

$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/archive.zip'));
```

## AWS S3 Setup

```php
use Aws\S3\S3Client;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\AwsS3 as Adapter;

$client = S3Client::factory(array(
    'key'    => '[your key]',
    'secret' => '[your secret]',
));

$filesystem = new Filesystem(new Adapter($client, 'bucket-name', 'optional-prefix'));
```

## Rackspace Setup

```php
use OpenCloud\OpenStack;
use OpenCloud\Rackspace;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Rackspace as Adapter;

$client = new OpenStack(Rackspace::UK_IDENTITY_ENDPOINT, array(
    'username' => ':username',
    'password' => ':password',
));

$store = $client->objectStoreService('cloudFiles', 'LON');
$container = $store->getContainer('flysystem');

$filesystem = new Filesystem(new Adapter($container));
```

## Dropbox Setup

```php
use Dropbox\Client;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Dropbox as Adapter;

$client = new Client($token, $appName);
$filesystem = new Filesystem(new Adapter($client, 'optional/path/prefix'));
```

## FTP Setup

```php
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Ftp as Adapter;

$filesystem = new Filesystem(new Adapter(array(
    'host' => 'ftp.example.com',
    'username' => 'username',
    'password' => 'password',

    /** optional config settings */
    'port' => 21,
    'root' => '/path/to/root',
    'passive' => true,
    'ssl' => true,
    'timeout' => 30,
)));
```

## SFTP Setup

```php
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Sftp as Adapter;

$filesystem = new Filesystem(new Adapter(array(
    'host' => 'example.com',
    'port' => 21,
    'username' => 'username',
    'password' => 'password',
    'privateKey' => 'path/to/or/contents/of/privatekey',
    'root' => '/path/to/root',
    'timeout' => 10,
)));
```

## WebDAV Setup

```php
$client = new Sabre\DAV\Client($settings);
$adapter = new League\Flysystem\Adapter\WebDav($client);
$flysystem = new League\Flysystem\Filesystem($adapter);
```

## Predis Caching Setup

```php
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local as Adapter;
use League\Flysystem\Cache\Predis as Cache;

$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/root'), new Cache);

// Or supply a client
$client = new Predis\Client;
$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/root'), new Cache($client));
```

## Memcached Caching Setup

```php
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local as Adapter;
use League\Flysystem\Cache\Memcached as Cache;

$memcached = new Memcached;
$memcached->addServer('localhost', 11211);

$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/root'), new Cache($memcached, 'storageKey', 300));
// Storage Key and expire time are optional
```

## Adapter Caching Setup

```php
use Dropbox\Client;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Dropbox;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Cache\Adapter;

$client = new Client('token', 'app');
$dropbox = new Dropbox($client, 'prefix');

$local = new Local('path');
$cache = new Adapter($local, 'file', 300);
// Expire time is optional

$filesystem = new Filesystem($dropbox, $cache);
```


## General Usage

__Write Files__

```php
$filesystem->write('filename.txt', 'contents');
```

__Update Files__

```php
$filesystem->update('filename.txt', 'new contents');
```

__Write or Update Files__

```php
$filesystem->put('filename.txt', 'contents');
```

__Read Files__

```php
$contents = $filesystem->read('filename.txt');
```

__Check if a file exists__

```php
$exists = $filesystem->has('filename.txt');
```

__Delete Files__

```php
$filesystem->delete('filename.txt');
```

__Rename Files__

```php
$filesystem->rename('filename.txt', 'newname.txt');
```

__Get Mimetypes__

```php
$mimetype = $filesystem->getMimetype('filename.txt');
```

__Get Timestamps__

```php
$timestamp = $filesystem->getTimestamp('filename.txt');
```

__Get File Sizes__

```php
$size = $filesystem->getSize('filename.txt');
```

__Create Directories__

```php
$filesystem->createDir('nested/directory');
```
Directories are also made implicitly when writing to a deeper path

```php
$filesystem->write('path/to/filename.txt', 'contents');
```

__Delete Directories__

```php
$filesystem->deleteDir('path/to/directory');
```

__Manage Visibility__

Visibility is the abstraction of file permissions across multiple platforms. Visibility can be either public or private.

```php
use League\Flysystem\AdapterInterface;
$filesystem->write('db.backup', $backup, [
    'visibility' => AdapterInterface::VISIBILITY_PRIVATE),
]);
// or simply
$filesystem->write('db.backup', $backup, ['visibility' => 'private']);
```

You can also change and check visibility of existing files

```php
if ($filesystem->getVisibility('secret.txt') === 'private') {
    $filesystem->setVisibility('secret.txt', 'public');
}
```

## Global visibility setting

You can set the visibility as a default, which prevents you from setting it all over the place.

```php
$filesystem = new League\Flysystem\Filesystem($adapter, $cache, [
    'visibility' => AdapterInterface::VISIBILITY_PRIVATE
]);
```

___List Contents___

```php
$contents = $filemanager->listContents();
```

The result of a contents listing is a collection of arrays containing all the metadata the file manager knows at that time. By default a you'll receive path info and file type. Additional info could be supplied by default depending on the adapter used.

Example:

```php
foreach ($contents as $object) {
    echo $object['basename'].' is located at'.$object['path'].' and is a '.$object['type'];
}
```

By default Flysystem lists the top directory non-recursively. You can supply a directory name and recursive boolean to get more precise results

```php
$contents = $flysystem->listContents('some/dir', true);
```

___List paths___

```php
$paths = $filemanager->listPaths();

foreach ($paths as $path) {
    echo $path;
}
```

___List with ensured presence of specific metadata___

```php
$listing = $flysystem->listWith(['mimetype', 'size', 'timestamp'], 'optional/path/to/dir', true);

foreach ($listing as $object) {
    echo $object['path'].' has mimetype: '.$object['mimetype'];
}
```

___Get file into with explicit metadata___

```php
$info = $flysystem->getWithMetadata('path/to/file.txt', ['timestamp', 'mimetype']);
echo $info['mimetype'];
echo $info['timestamp'];
```

## Using streams for reads and writes

```php
$stream = fopen('/path/to/database.backup', 'r+');
$flysystem->writeStream('backups/' . strftime('%G-%m-%d') . '.backup', $stream);

// Using write you can also directly set the visibility
$flysystem->writeStream('backups/' . strftime('%G-%m-%d') . '.backup', $stream, 'private');

// Or update a file with stream contents
$flysystem->updateStream('backups/' . strftime('%G-%m-%d') . '.backup', $stream);

// Retrieve a read-stream
$stream = $flysystem->readStream('something/is/here.ext');
$contents = stream_get_contents($stream);
fclose($stream);

// Create or overwrite using a stream.
$putStream = tmpfile();
fwrite($putStream, $contents);
rewind($putStream);
$filesystem->putStream('somewhere/here.txt', $putStream);
fclose($putStream);
```

## S3 and writeStream

In order to get the correct mime type for the object, supply it like so:

```php
$s3->writeStream('path/to/object.png', $stream, [
    'visibility' => 'public',
    'mimetype' => 'image/png',
]);
```

## Plugins

Need a feature which is not included in Flysystem's bag of tricks? Write a plugin!

```php
use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;

class MaximusAwesomeness implements PluginInterface
{
    protected $filesystem;

    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function getMethod()
    {
        return 'getDown';
    }

    public function handle($path = null)
    {
        $contents = $this->filesystem->read($path);

        return sha1($contents);
    }
}
```

Now we're ready to use the plugin

```php
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter;

$filesystem = new Filesystem(new Adapter\Local(__DIR__.'/path/to/files/'));
$filesystem->addPlugin(new MaximusAwesomeness);
$sha1 = $filesystem->getDown('path/to/file');
```

# Mount Manager

Flysystem comes with an wrapper class to easily work with multiple filesystem instances
from a single object. The `Flysystem\MountManager` is an easy to use container allowing
you do simplify complex cross-filesystem interactions.

Setting up a Mount Manager is easy:

```php
$ftp = new League\Flysystem\Filesystem($ftpAdapter);
$s3 = new League\Flysystem\Filesystem($s3Adapter);
$local = new League\Flysystem\Filesystem($localAdapter);

// Add them in the constructor
$manager = new League\Flysystem\MountManager(array(
    'ftp' => $ftp,
    's3' => $s3,
));

// Or mount them later
$manager->mountFilesystem('local', $local);
```

Now we do all the file operations we'd normally do on a `Flysystem\Filesystem` instance.

```php
// Read from FTP
$contents = $manager->read('ftp://some/file.txt');

// And write to local
$manager->write('local://put/it/here.txt', $contents);
```

This makes it easy to code up simple sync strategies.

```php
$contents = $manager->listContents('local://uploads', true);

foreach ($contents as $entry) {
    $update = false;

    if ( ! $manager->has('storage://'.$entry['path'])) {
        $update = true;
    }

    elseif ($manager->getTimestamp('local://'.$entry['path']) > $manager->getTimestamp('storage://'.$entry['path'])) {
        $update = true;
    }

    if ($update) {
        $manager->put('storage://'.$entry['path'], $manager->read('local://'.$entry['path']));
    }
}
```

# Enjoy.

Oh and if you've come down this far, you might as well follow me on [twitter](http://twitter.com/frankdejonge).
