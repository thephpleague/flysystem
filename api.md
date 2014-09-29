---
layout: default
permalink: /api/
title: API
---

# API

## General Usage

__Write Files__

~~~ php
$filesystem->write('filename.txt', 'contents');
~~~

__Update Files__

~~~ php
$filesystem->update('filename.txt', 'new contents');
~~~

__Write or Update Files__

~~~ php
$filesystem->put('filename.txt', 'contents');
~~~

__Read Files__

~~~ php
$contents = $filesystem->read('filename.txt');
~~~

__Check if a file exists__

~~~ php
$exists = $filesystem->has('filename.txt');
~~~

__Delete Files__

~~~ php
$filesystem->delete('filename.txt');
~~~

__Read and Delete__

~~~ php
$contents = $filesystem->readAndDelete('filename.txt');
~~~

__Rename Files__

~~~ php
$filesystem->rename('filename.txt', 'newname.txt');
~~~

__Copy Files__

~~~ php
$filesystem->copy('filename.txt', 'duplicate.txt');
~~~

__Get Mimetypes__

~~~ php
$mimetype = $filesystem->getMimetype('filename.txt');
~~~

__Get Timestamps__

~~~ php
$timestamp = $filesystem->getTimestamp('filename.txt');
~~~

__Get File Sizes__

~~~ php
$size = $filesystem->getSize('filename.txt');
~~~

__Create Directories__

~~~ php
$filesystem->createDir('nested/directory');
~~~
Directories are also made implicitly when writing to a deeper path

~~~ php
$filesystem->write('path/to/filename.txt', 'contents');
~~~

__Delete Directories__

~~~ php
$filesystem->deleteDir('path/to/directory');
~~~

__Manage Visibility__

Visibility is the abstraction of file permissions across multiple platforms. Visibility can be either public or private.

~~~ php
use League\Flysystem\AdapterInterface;
$filesystem->write('db.backup', $backup, [
    'visibility' => AdapterInterface::VISIBILITY_PRIVATE),
]);
// or simply
$filesystem->write('db.backup', $backup, ['visibility' => 'private']);
~~~

You can also change and check visibility of existing files

~~~ php
if ($filesystem->getVisibility('secret.txt') === 'private') {
    $filesystem->setVisibility('secret.txt', 'public');
}
~~~

## Global visibility setting

You can set the visibility as a default, which prevents you from setting it all over the place.

~~~ php
$filesystem = new League\Flysystem\Filesystem($adapter, $cache, [
    'visibility' => AdapterInterface::VISIBILITY_PRIVATE
]);
~~~

___List Contents___

~~~ php
$contents = $filemanager->listContents();
~~~

The result of a contents listing is a collection of arrays containing all the metadata the file manager knows at that time. By default a you'll receive path info and file type. Additional info could be supplied by default depending on the adapter used.

Example:

~~~ php
foreach ($contents as $object) {
    echo $object['basename'].' is located at'.$object['path'].' and is a '.$object['type'];
}
~~~

By default Flysystem lists the top directory non-recursively. You can supply a directory name and recursive boolean to get more precise results

~~~ php
$contents = $flysystem->listContents('some/dir', true);
~~~

___List paths___

~~~ php
$paths = $filemanager->listPaths();

foreach ($paths as $path) {
    echo $path;
}
~~~

___List with ensured presence of specific metadata___

~~~ php
$listing = $flysystem->listWith(['mimetype', 'size', 'timestamp'], 'optional/path/to/dir', true);

foreach ($listing as $object) {
    echo $object['path'].' has mimetype: '.$object['mimetype'];
}
~~~

___Get file into with explicit metadata___

~~~ php
$info = $flysystem->getWithMetadata('path/to/file.txt', ['timestamp', 'mimetype']);
echo $info['mimetype'];
echo $info['timestamp'];
~~~

## Using streams for reads and writes

~~~ php
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
~~~
