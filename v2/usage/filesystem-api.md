---
layout: default
title: Filesystem API
permalink: /v2/docs/usage/filesystem-api/
---

The Filesystem API is the most important interface Flysystem describes
when you want to _use_ Flysystem in your application.

For more information about the exception, read all about
[exception handling](/v2/docs/usage/exception-handling/).

---

## Writing files

Writing files can be done in two ways. You can use the contents of a file as
a `string` to write a file. In cases where you're writing large files, using
a `resource` to write a file is better. A resource allows the contents of
the file to be "streamed" to the new location, which has a very low memory
footprint.

When writing files, the directory you're writing to will be created
automatically if and when that is required in the filesystem you're writing to.
If your filesystem does not require directories to exist (like AWS S3), the
directory is _not_ created. This is a performance consideration. Of course,
you can always create the directory yourself by using the [createDirectory](#creating-a-directory)
operation.

### FilesystemWriter::write

```php
try {
    $filesystem->write($path, $contents, $config);
} catch (FilesystemException | UnableToWriteFile $exception) {
    // handle the error
}
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$contents`   | file contents                                 | `string`
`$config`     | An optional configuration array               | `array` (optional)

### FilesystemWriter::writeStream

```php
try {
    $filesystem->writeStream($path, $stream, $config);
} catch (FilesystemException | UnableToWriteFile $exception) {
    // handle the error
}
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$stream`     | file resource                                 | `resource`
`$config`     | An optional configuration array               | `array` (optional)

---

## Reading files

### FilesystemReader::read

Like writing a file, reading a file can be done in two ways. You can read the file
contents in full as a `string`, or "stream" it by obtaining a `resource`. Using the
`resource` allows you to stream the contents to a destination (local or to another
filesystem) in order to keep memory usage low.

```php
try {
    $response = $filesystem->read($path);
} catch (FilesystemException | UnableToReadFile $exception) {
    // handle the error
}
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$response`   | file contents                                 | `string`

### FilesystemReader::readStream

```php
try {
    $response = $filesystem->readStream($path);
} catch (FilesystemException | UnableToReadFile $exception) {
    // handle the error
}
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$response`   | file contents handle                          | `resource`

---

## Deleting

### FilesystemWriter::delete

```php
try {
    $filesystem->delete($path);
} catch (FilesystemException | UnableToDeleteFile $exception) {
    // handle the error
}
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`

### FilesystemWriter::deleteDirectory

```php
try {
    $filesystem->deleteDirectory($path);
} catch (FilesystemException | UnableToDeleteDirectory $exception) {
    // handle the error
}
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`

---

## Listing directory contents

### FilesystemReader::listContents

```php
try {
    $listing = $filesystem->listContents($path, $recursive);

    /** @var \League\Flysystem\StorageAttributes $item */
    foreach ($listing as $item) {
        $path = $item->path();

        if ($item instanceof \League\Flysystem\FileAttributes) {
            // handle the file
        } elseif ($item instanceof \League\Flysystem\DirectoryAttributes) {
            // handle the directory
        }
    }
} catch (FilesystemException $exception) {
    // handle the error
}
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$recursive`  | recursive or not (default false)              | `boolean` (optional)
`$listing`    | directory listing                             | `League\Flysystem\DirectoryListing`

---

## Retrieving metadata

### FilesystemReader::fileExists

```php
try {
    $fileExists = $filesystem->fileExists($path);
} catch (FilesystemException | UnableToRetrieveMetadata $exception) {
    // handle the error
}
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$fileExists` | whether or not a file exists                  | `boolean`

### FilesystemReader::lastModified

```php
try {
    $lastModified = $filesystem->lastModified($path);
} catch (FilesystemException | UnableToRetrieveMetadata $exception) {
    // handle the error
}
```

param          | description                                   | type
-------------- | --------------------------------------------- | -----------
`$path`        | location of a file                            | `string`
`$lastModified`| timestamp                                     | `int`

### FilesystemReader::mimeType

```php
try {
    $mimeType = $filesystem->mimeType($path);
} catch (FilesystemException | UnableToRetrieveMetadata $exception) {
    // handle the error
}
```

param          | description                                   | type
-------------- | --------------------------------------------- | -----------
`$path`        | location of a file                            | `string`
`$mimeType`    | mime-type                                     | `string`


### FilesystemReader::fileSize

```php
try {
    $fileSize = $filesystem->fileSize($path);
} catch (FilesystemException | UnableToRetrieveMetadata $exception) {
    // handle the error
}
```

param          | description                                   | type
-------------- | --------------------------------------------- | -----------
`$path`        | location of a file                            | `string`
`$fileSize`    | file size                                     | `int`

### FilesystemReader::visibility

```php
try {
    $visibility = $filesystem->visibility($path);
} catch (FilesystemException | UnableToRetrieveMetadata $exception) {
    // handle the error
}
```

param          | description                                   | type
-------------- | --------------------------------------------- | -----------
`$path`        | location of a file                            | `string`
`$visibility`  | visibility                                    | `string`

---

## Setting visibility

### FilesystemWriter::setVisibility

```php
try {
    $filesystem->setVisibility($path, $visibility);
} catch (FilesystemException | UnableToSetVisibility $exception) {
    // handle the error
}
```

param          | description                                   | type
-------------- | --------------------------------------------- | -----------
`$path`        | location of a file                            | `string`
`$visibility`  | visibility                                    | `string`

---

## Creating a directory

### FilesystemWriter::createDirectory

```php
try {
    $filesystem->createDirectory($path, $config);
} catch (FilesystemException | UnableToCreateDirectory $exception) {
    // handle the error
}
```

param          | description                                   | type
-------------- | --------------------------------------------- | -----------
`$path`        | location of a file                            | `string`
`$config`      | config array                                  | `array`

---

## Moving and copying

Moving and copying are both deterministic operations. This means they
will always overwrite the target location, and parent directories are
always created (if and when needed).

### FilesystemWriter::move

```php
try {
    $filesystem->move($source, $destination, $config);
} catch (FilesystemException | UnableToMoveFile $exception) {
    // handle the error
}
```

param          | description                                   | type
-------------- | --------------------------------------------- | -----------
`$source`      | location of a file                            | `string`
`$destination` | new location of the file                      | `string`
`$config`      | config array                                  | `array` (optional)



### FilesystemWriter::copy

```php
try {
    $filesystem->copy($source, $destination, $config);
} catch (FilesystemException | UnableToCopyFile $exception) {
    // handle the error
}
```

param          | description                                   | type
-------------- | --------------------------------------------- | -----------
`$source`      | location of a file                            | `string`
`$destination` | location for the file  copy                   | `string`
`$config`      | config array                                  | `array` (optional)

