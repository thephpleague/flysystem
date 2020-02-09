---
layout: default
title: Filesystem API
permalink: /v2/docs/usage/filesystem-api/
---

The Filesystem API is the most important interface Flysystem describes
when you want to _use_ Flysystem in your application.

For more information about the exception, read all about
[error handling](/v2/docs/usage/error-handling/).

---

## Writing files

### FilesystemWriter::write

```php
try {
    $filesystem->write($path, $contents, $config);
} catch (FilesystemError | UnableToWriteFile $exception) {
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
} catch (FilesystemError | UnableToWriteFile $exception) {
    // handle the error
}
```

param         | description                                   | type
------------- | --------------------------------------------- | -----------
`$path`       | location of a file                            | `string`
`$contents`   | file resource                                 | `resource`
`$config`     | An optional configuration array               | `array` (optional)

---

## Reading files

### FilesystemReader::read

```php
try {
    $response = $filesystem->read($path);
} catch (FilesystemError | UnableToReadFile $exception) {
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
} catch (FilesystemError | UnableToReadFile $exception) {
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
} catch (FilesystemError | UnableToDeleteFile $exception) {
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
} catch (FilesystemError | UnableToDeleteDirectory $exception) {
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
} catch (FilesystemError $exception) {
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
} catch (FilesystemError | UnableToRetrieveMetadata $exception) {
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
} catch (FilesystemError | UnableToRetrieveMetadata $exception) {
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
} catch (FilesystemError | UnableToRetrieveMetadata $exception) {
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
} catch (FilesystemError | UnableToRetrieveMetadata $exception) {
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
} catch (FilesystemError | UnableToRetrieveMetadata $exception) {
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
} catch (FilesystemError | UnableToSetVisibility $exception) {
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
} catch (FilesystemError | UnableToCreateDirectory $exception) {
    // handle the error
}
```

param          | description                                   | type
-------------- | --------------------------------------------- | -----------
`$path`        | location of a file                            | `string`
`$config`      | config array                                  | `array`

---

## Moving and copying

### FilesystemWriter::move

```php
try {
    $filesystem->move($source, $destination, $config);
} catch (FilesystemError | UnableToMoveFile $exception) {
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
} catch (FilesystemError | UnableToCopyFile $exception) {
    // handle the error
}
```

param          | description                                   | type
-------------- | --------------------------------------------- | -----------
`$source`      | location of a file                            | `string`
`$destination` | location for the file  copy                   | `string`
`$config`      | config array                                  | `array` (optional)

