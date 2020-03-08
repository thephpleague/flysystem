---
layout: default
permalink: /v1/docs/usage/filesystem-api/
redirect_from: 
    - /docs/usage/filesystem-api/
    - /api/
title: API
alternate_title: Filesystem API
---

The Filesystem API is the most important interface Flysystem describes
when you want to _use_ Flysystem in your application.

## Write Files

```php
$response = $filesystem->write($path, $contents [, $config]);
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$contents`   | file contents                                 | `string`
`$config`     | An optional configuration array               | `array`
`$response`   | success boolean                               | `bool`

---

## Write Files using a stream

```php
$response = $filesystem->writeStream($path, $resource [, $config]);
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$resource`   | file stream                                   | `resource`
`$config`     | An optional configuration array               | `array`
`$response`   | success boolean                               | `bool`

---

## Update Files

```php
$response = $filesystem->update($path, $contents [, $config]);
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$contents`   | file contents                                 | `string`
`$config`     | An optional configuration array               | `array`
`$response`   | success boolean                               | `bool`

---

## Update Files using a stream

```php
$response = $filesystem->updateStream($path, $resource [, $config]);
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$resource`   | file stream                                   | `resource`
`$config`     | An optional configuration array               | `array`
`$response`   | success boolean                               | `bool`

---

## Write or Update Files

```php
$response = $filesystem->put($path, $contents [, $config]);
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$contents`   | file contents                                 | `string`
`$config`     | An optional configuration array               | `array`
`$response`   | success boolean                               | `bool`

---

## Write or Update Files using a stream

```php
$response = $filesystem->putStream($path, $resource [, $config]);
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$resource`   | file stream                                   | `resource`
`$config`     | An optional configuration array               | `array`
`$response`   | success boolean                               | `bool`

---

## Read Files

```php
$contents = $filesystem->read($path);
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$contents`   | file contents or false on failure             | `string|false`

---

## Read Files as a stream

```php
$resource = $filesystem->readStream($path);
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$resource`   | file stream or false on failure               | `resource|false`

---

## Check if a file or directory exists

```php
$exists = $filesystem->has($path);
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file/directory                  | `string`
`$exists`     | whether it exists                             | `bool`

> This only has consistent behaviour for files, not directories. Directories
> are less important in Flysystem, they're created implicitly and often ignored because
> not every adapter (filesystem type) supports directories.

---

## Delete Files or Directories

```php
$response = $filesystem->delete($path);
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file/directory                  | `string`
`$response`   | success boolean                               | `bool`

---

## Read and Delete

```php
$contents = $filesystem->readAndDelete($path);
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$contents`   | file contents or false on failure             | `string|false`

---

## Rename Files

```php
$response = $filesystem->rename($from, $to);
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$from`       | location of a file                            | `string`
`$to`         | new location                                  | `string`
`$response`   | success boolean                               | `bool`

---

> When used with the Local adapter, this will rename a directory as well. This
> behaviour will not be present in V2.

## Copy Files

```php
$response = $filesystem->copy($from, $to);
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$from`       | location of a file                            | `string`
`$to`         | new location                                  | `string`
`$response`   | success boolean                               | `bool`

---

## Get Mimetypes

```php
$response = $filesystem->getMimetype($path);
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$response`   | mime-type or false on failure                 | `string|false`

---

## Get Timestamps

This function returns the last updated timestamp.

```php
$response = $filesystem->getTimestamp($path);
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$response`   | timestamp of modification or false on failure | `integer|false`

---

## Get File Sizes

```php
$response = $filesystem->getSize($path);
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$response`   | size of a file or false on failure            | `integer|false`

---

## Create Directories

```php
$response = $filesystem->createDir($path);
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$response`   | success boolean                               | `boolean`

If needed, directories are also made implicitly when writing to a deeper path.
In general creating a directory is __not__ required in order to write
to it.

Some filesystems don't require directories at all, and for those filesystems (like
AWS S3) _no directories are created when writing files_.

---

## Delete Directories

Deleting directories is always done recursively.

```php
$response = $filesystem->deleteDir($path);
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$response`   | success boolean                               | `boolean`


---

## Manage Visibility

Visibility is the abstraction of file permissions across multiple platforms. Visibility can be either public or private.

```php
use League\Flysystem\AdapterInterface;

$filesystem->write($path, $contents, [
    'visibility' => AdapterInterface::VISIBILITY_PRIVATE
]);

// Or simply
$filesystem->write($path, $contents, ['visibility' => 'private']);
```

You can also change and check visibility of existing files:

```php
if ($filesystem->getVisibility($path) === 'private') {
    $filesystem->setVisibility($path, 'public');
}
```

---

## Global visibility setting

You can set the visibility as a default, which prevents you from setting it all over the place.

```php
$filesystem = new League\Flysystem\Filesystem($adapter, [
    'visibility' => AdapterInterface::VISIBILITY_PRIVATE
]);
```

---

## List Contents

```php
$contents = $filesystem->listContents($path, $recursive);
```

The result of a contents listing is a collection of arrays containing all the metadata the file manager knows at that time. By default you'll receive path info and file type. Additional info could be supplied by default depending on the adapter used.

Example:

```php
foreach ($contents as $object) {
    echo $object['basename'].' is located at '.$object['path'].' and is a '.$object['type'];
}
```

By default Flysystem lists the top directory non-recursively. You can supply a directory name and recursive boolean to get more precise results

```php
$contents = $filesystem->listContents('some/dir', true);
```

---

## Using streams for reads and writes

Some SDK's close streams after consuming them, therefore, before calling fclose on the resource, check if it's still valid using <code>is_resource</code>.

```php
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
```
