---
layout: default
permalink: /api/
title: API
---

# API

## General Usage

__Write Files__

~~~ php
array $result = $filesystem->write('path/to/file.txt', 'contents');
~~~

The array will contain at least the keys path and contents; it may also
contain visibility, mimetype and size.


__Update Files__

~~~ php
array $result = $filesystem->update('path/to/file.txt', 'new contents');
~~~

The array will contain at least the keys path and contents; it may also
contain visibility, mimetype and size.


__Read Files__

~~~ php
array $result = $filesystem->read('path/to/file.txt');
~~~

The array will contain the keys path and contents.

__Check if a file exists__

~~~ php
boolean $bool = $filesystem->has('path/to/file.txt');
~~~

__NOTE__: This only has consistent behaviour for files, not directories. Directories
are less important in Flysystem, they're created implicitly and often ignored because
not every adapter (filesystem type) supports directories. This method is optional but
most adapters implement it.

__Delete Files__

~~~ php
boolean $bool = $filesystem->delete('path/to/file.txt');
~~~

__Read and Delete__

~~~ php
$contents = $filesystem->readAndDelete('path/to/file.txt');
~~~

__NOTE__: This method is deprecated.

__Rename Files__

~~~ php
boolean $bool = $filesystem->rename('filename.txt', 'newname.txt');
~~~

__Copy Files__

~~~ php
boolean $bool = $filesystem->copy('filename.txt', 'duplicate.txt');
~~~

__Get Mimetypes__

~~~ php
array $result = $filesystem->getMimetype('path/to/file.txt');
~~~

The array will contain the key mimetype and may optionally contain the keys path,
type, size, and timestamp.

__Get Timestamps__

~~~ php
array $result = $filesystem->getTimestamp('path/to/file.txt');
~~~

The array will contain the key timestamp and may optionally contain the keys path,
type, size, and mimetype.

__Get File Sizes__

~~~ php
array $result = $filesystem->getSize('path/to/file.txt');
~~~

The array will contain the key size and may optionally contain the keys path,
type, timestamp, and mimetype.

__Create Directories__

~~~ php
$filesystem->createDir('path/to/nested/directory');
~~~

Directories are also made implicitly when writing to a deeper path.

__NOTE__: The array will contain the key path and may optionally contain the keys
type, size, timestamp, and mimetype.


~~~ php
array $result = $filesystem->write('path/to/file.txt', 'contents');
~~~

The array will contain the keys contents and mimetype. It may optionally contain the keys
type, size, visibility, and path.

__Delete Directories__

~~~ php
boolean $bool = $filesystem->deleteDir('path/to/directory');
~~~
The above method will delete directories recursively

__NOTE__: All paths used by Flysystem API are relative to the adapter root directory.

__Manage Visibility__

Visibility is the abstraction of file permissions across multiple platforms. Visibility can be either public or private.

~~~ php
use League\Flysystem\AdapterInterface;

$filesystem->write('db.backup', $backup, [
    'visibility' => AdapterInterface::VISIBILITY_PRIVATE
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
$filesystem = new League\Flysystem\Filesystem($adapter, [
    'visibility' => AdapterInterface::VISIBILITY_PRIVATE
]);
~~~

__List Contents__

~~~ php
$contents = $filemanager->listContents();
~~~

The result of a contents listing is a collection of arrays containing all the metadata the file manager knows at that time. By default you'll receive path info and file type. Additional info could be supplied by default depending on the adapter used.

Example:

~~~ php
foreach ($contents as $object) {
    echo $object['basename'].' is located at'.$object['path'].' and is a '.$object['type'];
}
~~~

By default Flysystem lists the top directory non-recursively. You can supply a directory name and recursive boolean to get more precise results

~~~ php
$contents = $filesystem->listContents('some/dir', true);
~~~

__List paths__

~~~ php
$filesystem->addPlugin(new ListPaths());

$paths = $filesystem->listPaths();

foreach ($paths as $path) {
    echo $path;
}
~~~

__List with ensured presence of specific metadata__

~~~ php
$listing = $filesystem->listWith(['mimetype', 'size', 'timestamp'], 'optional/path/to/dir', true);

foreach ($listing as $object) {
    echo $object['path'].' has mimetype: '.$object['mimetype'];
}
~~~

__Get file into with explicit metadata__

~~~ php
$info = $filesystem->getWithMetadata('path/to/file.txt', ['timestamp', 'mimetype']);
echo $info['mimetype'];
echo $info['timestamp'];
~~~

__NOTE__: This requires the `League\Flysystem\Plugin\GetWithMetadata` plugin.

## Using streams for reads and writes

<p class="message-notice">
Some SDK's close streams after consuming them, therefore, before calling fclose on the resource, check if it's still valid using <code>is_resource</code>.
</p>

~~~ php
$stream = fopen('/path/to/database.backup', 'r+');
$filesystem->writeStream('backups/'.strftime('%G-%m-%d').'.backup', $stream);

// Using write you can also directly set the visibility
$filesystem->writeStream('backups/'.strftime('%G-%m-%d').'.backup', $stream, [
    'visibility' => AdapterInterface::VISIBILITY_PRIVATE
]);

if (is_resource($stream)) {
    fclose($stream);
}

// Or update a file with stream contents
$filesystem->updateStream('backups/'.strftime('%G-%m-%d').'.backup', $stream);

// Retrieve a read-stream
$stream = $filesystem->readStream('something/is/here.ext');
$contents = stream_get_contents($stream);
fclose($stream);

// Create or overwrite using a stream.
$putStream = tmpfile();
fwrite($putStream, $contents);
rewind($putStream);
$filesystem->putStream('somewhere/here.txt', $putStream);

if (is_resource($putStream)) {
    fclose($putStream);
}
~~~
