---
layout: default
title: Visibility
permalink: /v2/docs/visibility/
---

## What is visibility?

Flysystem provides a simplified approach to dealing with permissions, called visibility.
Visibility, is a string based configuration option that allows you set permissions on
files and directories. The visibility conversion classes and interfaces give you
fine-grained control over permissions for every adapter.

## Portable visibility

By default, all the adapters understand a public/private visibility setting. This
setting is translated to the adapter's own way of interpreting this setting. The 
interpretation is aimed to be similar in effect across all adapters. This can,
however, not fit your needs. In this case you can implement your own visibility
strategy.

## Unix-style visibility

For filesystems with a Unix-style visibility system, like on MacOS and Linux, these string
values are translated to permissions like `0600` or `0744`. The
[unix-style visibility](/v2/docs/usage/unix-visibility/) module provides an implementation
to configure how you want these values to be interpreted.

Unix-style visibility is used for:

- LocalFilesystemAdapter
- PhpseclibV2Adapter
- FtpAdapter 

## Custom visibility

Every adapter has its own visibility conversion interface you can use to implement
your own visibility strategy.

Adapter | Visibility Interface
--- | ---
AsyncAws S3 | `League\Flysystem\AsyncAwsS3\VisibilityConverter`
AWS S3 | `League\Flysystem\AwsS3V3\VisibilityConverter`
Local | `League\Flysystem\UnixVisibility\VisibilityConverter`
FTP | `League\Flysystem\UnixVisibility\VisibilityConverter` 
SFTP | `League\Flysystem\UnixVisibility\VisibilityConverter` 
