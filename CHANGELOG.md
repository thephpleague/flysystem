# Version 2.x Changelog

## 2.0.0-UNRELEASED

## Changes

* Renamed AwsS3V3Filesystem to AwsS3V3Adapter (in line with other adapter names).
* Renamed the PHPSecLibV2 package to PhpseclibV2, Renamed the FTP package to Ftp.
* Public key and ss-agent authentication support for Sftp

## Fixes

* Allow creation of files with empty streams.

## 2.0.0-alpha.3 2020-03-21

## Fixes

* Corrected the required version for the sub-split packages.

## 2.0.0-alpha.2 2020-03-17

## Changes

* The `League\Flysystem\FilesystemAdapter::listContents` method returns an `iterable` instead of a `Generator`.
* The `League\Flysystem\DirectoryListing` class accepts an `iterable` instead of a `Generator`.

## 2.0.0-alpha.1 2020-03-09

* Initial 2.0.0 alpha release
