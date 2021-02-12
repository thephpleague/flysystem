# Version 2.x Changelog

## 2.0.2 - 2020-12-28

## Fixed

* Corrected the ignored exports for Ftp

## 2.0.1 - 2020-12-28

## Fixed

* Corrected the ignored exports for Phpseclib

## 2.0.0 - 2020-11-24

## Changed

- string type added to all visibility input

## Added

- Google Cloud Storage adapter.

## 2.0.0-beta.3 - 2020-08-23

### Added

- UnableToCheckFileExistence error introduced
- Mount manager is re-introduced

### Fixes

- Allow FTP filenames to contain special characters.
- Prevent resources of incorrect types to be passed.

### Improved

- [AWS] By default, make sure readStream resources are streamed over HTTP.

### Added

- DirectoryAttributes now have a `lastModified` accessor.

## 2.0.0-beta.2 - 2020-08-08

### Fixes

- Allow listing of top-level directory for AWS S3
- Ensure the adapters can use the correct beta release.

## 2.0.0-beta.1 - 2020-08-04

### Changes

- Small code optimizations
- Add global options array to AwsS3V3Adapter like in V1

## 2.0.0-alpha.4 - 2020-07-26

### Changes

* Renamed AwsS3V3Filesystem to AwsS3V3Adapter (in line with other adapter names).
* Renamed the PHPSecLibV2 package to PhpseclibV2, Renamed the FTP package to Ftp.
* Public key and ss-agent authentication support for Sftp

### Fixes

* Allow creation of files with empty streams.

## 2.0.0-alpha.3 - 2020-03-21

### Fixes

* Corrected the required version for the sub-split packages.

## 2.0.0-alpha.2 - 2020-03-17

### Changes

* The `League\Flysystem\FilesystemAdapter::listContents` method returns an `iterable` instead of a `Generator`.
* The `League\Flysystem\DirectoryListing` class accepts an `iterable` instead of a `Generator`.

## 2.0.0-alpha.1 - 2020-03-09

* Initial 2.0.0 alpha release
