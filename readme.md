# Flysystem by [@frankdejonge](http://twitter.com/frankdejonge)

[![Build Status](https://travis-ci.org/FrenkyNet/Flysystem.png)](https://travis-ci.org/FrenkyNet/Flysystem)
[![Latest Stable Version](https://poser.pugx.org/frenkynet/flysystem/v/stable.png)](https://packagist.org/packages/frenkynet/flysystem)
[![Total Downloads](https://poser.pugx.org/frenkynet/flysystem/downloads.png)](https://packagist.org/packages/frenkynet/flysystem)
[![Coverage Status](https://coveralls.io/repos/FrenkyNet/Flysystem/badge.png)](https://coveralls.io/r/FrenkyNet/Flysystem)
[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/FrenkyNet/flysystem/trend.png)](https://bitdeli.com/free)

Flysystem is a filesystem abstraction which allows you to easily swap out a local filesystem for a remote one.

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
        "frenkynet/flysystem": "0.1.*"
    }
}
```

## Adapters

* Local
* Amazon Web Services - S3
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

## Local Setup

```php
use Flysystem\Filesystem;
use Flysystem\Adapter\Local as Adapter;

$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/root'));
```

## Zip Archive Setup

```php
use Flysystem\Filesystem;
use Flysystem\Adapter\Zip as Adapter;

$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/archive.zip'));
```

## AwsS3 Setup

```php
use Aws\S3\S3Client;
use Flysystem\Filesystem;
use Flysystem\Adapter\AwsS3 as Adapter;

$client = S3Client::factory(array(
    'key'    => '[your key]',
    'secret' => '[your secret]',
));

$filesystem = new Filesystem(new Adapter($client, 'bucket-name', 'optional-prefix'));
```

## Dropbox Setup

```php
use Dropbox\Client;
use Flysystem\Filesystem;
use Flysystem\Adapter\Dropbox as Adapter;

$client = new Client($token, $appName);
$filesystem = new Filesystem(new Adapter($client, 'optional/path/prefix'));
```

## Ftp Setup

```php
use Flysystem\Filesystem;
use Flysystem\Adapter\Ftp as Adapter;

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

## Sftp Setup

```php
use Flysystem\Filesystem;
use Flysystem\Adapter\Sftp as Adapter;

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
$adapter = new Flysystem\Adapter\WebDav($client);
$flysystem = new Flisystem\Filesystem($adapter);
```

## Predis Caching Setup

```php
use Flysystem\Filesystem;
use Flysystem\Adapter\Local as Adapter;
use Flysystem\Cache\Predis as Cache;

$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/root'), new Cache);

// Or supply a client
$client = new Predis\Client;
$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/root'), new Cache($client));
```

## Memcached Caching Setup

```php
use Flysystem\Filesystem;
use Flysystem\Adapter\Local as Adapter;
use Flysystem\Cache\Memcached as Cache;

$memcached = new Memcached;
$memcached->addServer('localhost', 11211);
$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/root'), new Cache($memcached, 'storageKey', 300));
// Storage Key and expire time are optional
```

## General Usage

__Write Files__

```php
$filemanager->write('filename.txt', 'contents');
```

__Update Files__

```php
$filemanager->update('filename.txt', 'new contents');
```

__Write or Update Files__

```php
$filemanager->put('filename.txt', 'contents');
```

__Read Files__

```php
$contents = $filemanager->read('filename.txt');
```

__Check if a file exists__

```php
$exists = $filemanager->has('filename.txt');
```

__Delete Files__

```php
$filemanager->delete('filename.txt');
```

__Rename Files__

```php
$filemanager->rename('filename.txt', 'newname.txt');
```

__Get Mimetypes__

```php
$mimetype = $filemanager->getMimetype('filename.txt');
```

__Get Timestamps__

```php
$timestamp = $filemanager->getTimestamp('filename.txt');
```

__Get File Sizes__

```php
$size = $filemanager->getSize('filename.txt');
```

__Create Directories__

```php
$filemanager->createDir('nested/directory');
```
Directories are also made implicitly when writing to a deeper path

```php
$filemanager->write('path/to/filename.txt', 'contents');
```

__Delete Directories__

```php
$filemanager->deleteDir('path/to/directory');
```

__Manage Visibility__

Visibility is the abstraction of file permissions across multiple platforms. Visibility can be either public or private.

```php
use Flysystem\AdapterInterface;
$filesystem->write('db.backup', $backup, AdapterInterface::VISIBILITY_PRIVATE);
// or simply
$filesystem->write('db.backup', $backup, 'private');
```

You can also change and check visibility of existing files

```php
if ($filesystem->getVisibility('secret.txt') === 'private') {
	$filesystem->setVisibility('secret.txt', 'public');
}
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

___Get file into with explicid metadata___

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

## Plugins

Need a feature which is not included in Flysystem's bag of trick? Write a plugin!

```php
use Flysystem\FilesystemInterface;
use Flysystem\PluginInterface;

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
use Flysystem\Filesystem;
use Flysystem\Adapter;

$filesystem = new Filesystem(new Adapter\Local(__DIR__.'/path/to/files/'));
$filesystem->addPlugin(new MaximusAwesomeness);
$sha1 = $filesystem->getDown('path/to/file');
```

# Mount Manager

Flysystem comes with an wrapper class to easilly work with multiple filesystem instances
from a single object. The `Flysystem\MountManager` is an easy to use container allowing
you do simplify complex cross-filesystem interactions.

Setting up a Mount Manager is easy:

```php
$ftp = new Flysystem\Filesystem($ftpAdapter);
$s3 = new Flysystem\Filesystem($s3Adapter);
$local = new Flysystem\Filesystem($localAdapter);

// Add them in the constructor
$manager = new Flysystem\MountManager(array(
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

This makes is easy to code up simple sync strategies.

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
