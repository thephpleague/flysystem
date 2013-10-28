# Flysystem

Flysystem is a filesystem abstraction which allows you to easiliy swap out a local filesystem for a remote one.

# Goals

* Have a generic API for handling common tasks across multiple file storage engines.
* Have consistent output which you can rely on.
* Integrate well with other packages/frameworks.
* Be cacheable.
* Emulate directories in systems that support non, like AwsS3.

## Adapters

* Local
* Amazon Web Services - S3

## Caching

* Memory (array caching)
* Redis (through Predis)

## Local Setup

```
use Flysystem\Filesystem;
use Flysystem\Adapter\Local as Adapter;

$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/root'));
```
## AwsS3 Setup

```
use Aws\S3\S3Client;
use Flysystem\Filesystem;
use Flysystem\Adapter\AwsS3 as Adapter;

$client = S3Client::factory(array(
    'key'    => '[your key]',
    'secret' => '[your secret]',
));

$filesystem = new Filesystem(new Adapter($client, 'bucket-name', 'optional-prefix'));
```

## Predis Caching Setup

```
use Flysystem\Filesystem;
use Flysystem\Adapter\Local as Adapter;
use Flysystem\Cache\Predis as Cache;

$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/root'), new Cache);

// Or supply a client
$client = new Predis\Client;
$filesystem = new Filesystem(new Adapter(__DIR__.'/path/to/root'), new Cache($client);
```

## General Usage

### Write Files

```
$filemanager->write('filename.txt', 'contents');
```

### Read Files

```
$contents = $filemanager->read('filename.txt');
```

### Update Files

```
$filemanager->update('filename.txt', 'new contents');
```

### Delete Files

```
$filemanager->delete('filename.txt');
```

### Rename Files

```
$filemanager->rename('filename.txt', 'newname.txt');
```

### Get Mimetypes

```
$mimetype = $filemanager->getMimetype('filename.txt');
```

### Get Timestamps

```
$timestamp = $filemanager->getTimestamp('filename.txt');
```

### Get File Sizes

```
$size = $filemanager->getSize('filename.txt');
```

### Create Directories

```
$filemanager->createDir('nested/directory');
```
Directories are also made implicitly when writing to a deeper path

```
$filemanager->write('path/to/filename.txt', 'contents');
```

### Delete Directories

```
$filemanager->deleteDir('path/to/directory');
```

### Manage Visibility

Visibility is the abstraction of file permissions across multiple platforms. Visibility can be either public or private.

```
use Flysystem\AdapterInterface;
$filesystem->write('db.backup', $backup, AdapterInterface::VISIBILITY_PRIVATE);
// or simply
$filesystem->write('db.backup', $backup, 'private');
```

You can also change and check visibility of existing files

```
if ($filesystem->getVisibility('secret.txt') === 'private') {
	$filesystem->setVisibility('secret.txt', 'public');
}
```

### List Contents

```
$contents = $filemanager->listContents();
```

The result of a contents listing is a collection of arrays containing all the metadata the file manager knows at that time. By default a you'll receive path info and file type. Additional info could be supplied by default depending on the adapter used.

Example:

```
foreach ($contents as $object) {
	echo $object['base name'].' is located at'.$object['path'].' and is a '.$object['type'];
}
```

# Enjoy.