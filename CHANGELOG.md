# Changelog

## 3.0.1 - 2022-01-15

### Fixes

* [ZipArchive] delete top-level directory too when deleting a directory
* [GoogleCloudStorage] Use listing to check for directory existence (consistency)
* [GoogleCloudStorage] Fixed bug where exceptions were not thrown 
* [AwsS3V3] Allow passing options for controlling multi-upload options (#1396)
* [Local] Convert windows-style directory separator to unix-style (#1398)

## 3.0.0 - 2022-01-13

### Added

* FilesystemReader::has to check for directory or file existence
* FilesystemReader::directoryExists to check for directory existence
* FilesystemReader::fileExists to check for file existence
* FilesystemAdapter::directoryExists to check for directory existence
* FilesystemAdapter::fileExists to check for file existence

## 2.4.0 - 2022-01-04

### Added

- [SFTP V3] New adapter officially published

## 2.3.2 - 2021-11-28

### Fixed

- [FTP] Check for FTP\Connection object in addition to a `resource` for connectivity checks and connection handling.
- [Local] Simplify writeStream, as a bonus, have an EXT_LOCK on it now by default.

## 2.3.1 - 2021-09-22

### Fixed

- [ZipArchive] copy stream, the ziparchive is closed after getting the stream
- [Core] PHP 8.1 compatibility updates
- [LocalFilesystem] parse permissions during listing
- [LocalFilesystem] clear realstatcache
- [FTP] PHP 8.1 compatibility updates
- [Core] Upgraded PHP-CS-Fixer

## 2.3.0 - 2021-09-22

### Added

- [GoogleCloudStorage] Make it possible to set an empty prefix (#1358)
- [GoogleCloudStorage] Added possibility not to set visibility (#1356)

## 2.2.3 - 2021-08-18

### Fixed

- [Core] Corrected exception message for UnableToCopyFile.

## 2.2.2 - 2021-08-18

### Fixed

- [Core] Ensure the sorted directory listing can be iterated over (#1342).

## 2.2.1 - 2021-08-17

### Fixed

- [FTP] use original path when ensuring the parent directory exists during `move` operation.
- [FTP] do not fail setting UTF-8 when the server is already on UTF-8.
 
## 2.2.0 - 2021-07-20

### Added

* [Core] Added sortByPath on the directory listing to allows content listings to be sorted. 

## 2.1.1 - 2021-06-24

### Fixed

* [Core] Whitespace normalization now no longer strips funky whitespace but throws an exception.

## 2.1.0 - 2021-05-25

### Added

* [Core] the DirectoryAttributes now have an `extraMetadata` like files do.

### Fixed

* [AwsS3V3] Allow the ACL config option to take precedence over the visibility key.

## 2.0.8 - 2021-05-15

### Fixed

* [SFTP] Don't fail listing contents when a directory does not exist (#1301)

## 2.0.7 - 2021-05-13

### Fixed

* [LocalFilesystem] convert windows style paths to unix style paths on listing

## 2.0.6 - 2021-05-13

### Fixed

* [AsyncAwsS3] do not urlencode CopySource arguments (#1302)

## 2.0.5 - 2021-04-11

### Fixed

* [AwsS3] ensure write errors are turned into exceptions. 

## 2.0.4 - 2021-02-13

### Fixed

* [InMemory] Corrected how the file size is determined.

## 2.0.3 - 2021-02-09

### Fixed

* [AwsS3V3] Use the $config array during the copy operation.
* [Ftp] Close FTP connections when the object is destructed.
* [Core] Allow for an absolute root path of `/`.

## 2.0.2 - 2020-12-28

### Fixed

* Corrected the ignored exports for Ftp

## 2.0.1 - 2020-12-28

### Fixed

* Corrected the ignored exports for Phpseclib

## 2.0.0 - 2020-11-24

### Changed

- string type added to all visibility input

### Added

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
