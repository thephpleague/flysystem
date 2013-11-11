# Flysystem [![Build Status](https://travis-ci.org/FrenkyNet/Flysystem.png)](https://travis-ci.org/FrenkyNet/Flysystem) [![Latest Stable Version](https://poser.pugx.org/frenkynet/flysystem/v/stable.png)](https://packagist.org/packages/frenkynet/flysystem) [![Total Downloads](https://poser.pugx.org/frenkynet/flysystem/downloads.png)](https://packagist.org/packages/frenkynet/flysystem) by [@frankdejonge](http://twitter.com/frankdejonge)

Flysystem is a filesystem abstraction which allows you to easiliy swap out a local filesystem for a remote one.

# Goals

* Have a generic API for handling common tasks across multiple file storage engines.
* Have consistent output which you can rely on.
* Integrate well with other packages/frameworks.
* Be cacheable.
* Emulate directories in systems that support non, like AwsS3.

# Installation

Trough Composer, obviously:

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

### Planned Adapters

* Azure
* PR's welcome?

## Caching

* Memory (array caching)
* Redis (through Predis)

## Local Setup

```php
use Flysystem\Filesystem;
use Flysystem\Adapter\Local as Adapter;

$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/root'));
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
$filesystem = new Filesystem(new Adapter($client), 'optional/path/prefix');
```

## Ftp Setup

```php
use Flysystem\Filesystem;
use Flysystem\Adapter\Ftp as Adapter;

$filesystem = new Filesystem(new Adapter(array(
	'host' => 'ftp.example.com',
	'port' => 21,
	'username' => 'username',
	'password' => 'password',
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
	'privateKey' => 'path/to/or/contents/of/privatekey'
	'root' => '/path/to/root',
	'timeout' => 10,
)));
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

## General Usage

__Write Files__

```php
$filemanager->write('filename.txt', 'contents');
```

__Read Files__

```php
$contents = $filemanager->read('filename.txt');
```

__Update Files__

```php
$filemanager->update('filename.txt', 'new contents');
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

### List Contents

```php
$contents = $filemanager->listContents();
```

The result of a contents listing is a collection of arrays containing all the metadata the file manager knows at that time. By default a you'll receive path info and file type. Additional info could be supplied by default depending on the adapter used.

Example:

```php
foreach ($contents as $object) {
	echo $object['base name'].' is located at'.$object['path'].' and is a '.$object['type'];
}
```

### List paths

```php
$paths = $filemanager->listPaths();

foreach ($paths as $path) {
	echo $path;
}
```

### List with ensured presence of precific metadata

```php
$listing = $filesystem->listWith('mimetype', 'size', 'timestamp');

foreach ($listing as $object) {
	echo $object['path'].' has mimetype: '.$object['mimetype'];
}
```

# Enjoy.
