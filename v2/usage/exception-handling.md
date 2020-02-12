---
layout: default
title: Error Handling
permalink: /v2/docs/usage/exception-handling/
---

For every action operation in Flysystem, there's an exception telling you what
went wrong. For example: Are you trying to write a file? An `UnableToWriteFile`
exception is thrown when something went wrong.

For every type of operation there are exceptions:

operation | exception
--- | ---
write/writeStream | `League\Flysystem\UnableToWriteFile`  
read/readStream | `League\Flysystem\UnableToReadFile`
delete | `League\Flysystem\UnableToDeleteFile`
copy | `League\Flysystem\UnableToCopyFile`
move | `League\Flysystem\UnableToMoveFile`
setVisibility | `League\Flysystem\UnableToSetVisibility`
createDirectory | `League\Flysystem\UnableToCreateDirectory`
deleteDirectory | `League\Flysystem\UnableToDeleteDirectory`
all metadata getters | `League\Flysystem\UnableToRetrieveMetadata`

## Generic exception marker

Each and every exception thrown in Flysystem is marked with the
`League\Flysystem\FilesystemException` interface. While every exception
is uniquely named to describe what is actually going on, this marker
allows you to catch all of them. This allows you to have a "best of
both worlds" situation where catching exceptions can be as fine-grained
or as coarse as you need it to be.

## Always receive Flysystem exceptions

Whenever you're interacting with Flysystem, you'll only receive
exceptions coming from Flysystem. Whenever an underlying implementation
throws an error, the adapters catch it and throw the appropriate error.
When doing so, the previous exception is always brought along, so you'll
always know what caused the error in the first place.

## No warning!

Every warning is suppressed, but the errors are not lost. Whenever there
is valuable information in the error message, the message is brought along
with the exception.
