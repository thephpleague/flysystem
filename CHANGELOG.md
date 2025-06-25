# Changelog

## 3.30.0 - 2025-06-25

### Changes

- [MongoDB] Allow v2
- [AsyncAWS] Encode/decode object identifiers
- [GoogleCloudStorage] Add option to stream responses
- [Local] Clear stat cache consistently
- [SFTP] Add option to disconnect connections on destruction
- [WebDAV] Deal with 405 case when trying to create a directory that already exists.

## 3.29.1 - 2024-10-08

### Fixes

- Normalise path for checksum call

## 3.29.0 - 2024-09-29

### Changes

- [FTP] Add error context from error messages
- [SFTP] overwrite on move
- [AWS S3] same path copy/move no-op
- [Async S3] transform error to flysystem errors

## 3.28.0 - 2024-05-22

### Added

- MongoDB GridFS adapter (by @GromNaN)

### Fixed

- PHP 8.3 directory listing issue for the FTP adapter (by @lanz1)

## 3.27.0 - 2024-04-07

### Fixed

- Corrected AWS SSE-C Options
- Handle MetadataDirective gracefully.

## 3.26.0 - 2024-03-25

### Fixed

- Make SFTP connectivity pinging an opt-in feature.

### Added 

- Add `add_content_md5` option to AWS S3 (#1774)
- Added AWS SSE-C options (#1773)

## 3.25.1 - 2024-03-16

### Fixed

- Cleanup connection instance after disconnecting SFTP connection.
- Fix upcoming PHP 8.4 deprecations (#1772) 

## 3.25.0 - 2024-03-09

### Added

- [MountManager] added ability to (dangerously) mount additional filesystems
- [FTP] added `disconnect` method to proactively close connections
- [SFTP V3] added `disconnect` method to proactively close connections

## 3.24.0 - 2024-02-04

### Fixes

- Updated method signatures to match upgraded dependency signatures for overrides (#1748, #1746)
- Added missing path prefixing in FTP implementation (#1747)

### Changes

- Updated string assertions to use PHP 8 functions (#1750, #1749))

## 3.23.1 - 2024-01-26

### Changes

- Updated license year

## 3.23.0 - 2023-12-04

### Fixed

- Fixed upstream regression caused by resolving inconclusive mime-type.

### Added

- Made inconclusive mime-type resolving configurable on the local adapter.

## 3.22.0 - 2023-12-03

### Changes

- Prevent double directory creation with lazy root creation for Local filesystem.

### Fixes

- Resolve to "inconclusive" mimetype instead of causing a type error by @GuySartorelli
- Corrected spelling of a configuration key for the Azure adapter by @shineability

### Additions

- MountManager::extend allows for immutable dynamic extension of the mount manager.
- Added a new abstract DecoratedAdapter for easier decoration of adapters by @jnoordsij

## 3.21.0 - 2023-11-18

### Changes

- Retain visibility on local copy for local FS, in line with other adapter (#1730) by @jnoordsij

## 3.20.0 - 2023-11-14

### Changed

- Normalise paths for public and temporary URLs (#1727)

## 3.19.0 - 2023-11-07

### Added

- Configuration option to specify if visibility should be retained during copy/move operations
- InMemoryFilesystemAdapter now supports visibility changes on move and copy.
- Default visibility options are ignored when moving/copying while respecting visibility retention settings.
- Local filesystem implementation now allows setting visibility on move and copy.

## 3.18.0 - 2023-10-05

### Added

- Configuration option to specify how to handle same path copy/move operations (#1715)

## 3.17.0 - 2023-10-05

### Added

- [AsyncAWS] Added support for version 2.0 of async-aws/{s3,simple-s3}

## 3.16.0 - 2023-09-07

### Added

- [AsyncAws] Allow specifying `get_object_options` for temporary URL generation

### Fixed

- [ZipArchive] override on move
- [WebDAV] encode path for propfind actions
- [PathPrefixing]  [#1686](https://github.com/thephpleague/flysystem/issues/1686)

## 3.15.1 - 2023-05-04

### Fixed

- Remove duplicate class caused by package extractin and inclusion

## 3.15.0 - 2023-05-04

### Added

- Extracted the local adapter as a standalone package

### Changed

- Removed readme's from shipped artefacts.

## 3.14.0 - 2023-04-11

### Added

- Made disabling stat cache configurable for SFTP adapters.

## 3.13.0 - 2023-04-11

### Fixed

- AsyncAwsS3 object deletion now chunks per 100 objects to prevent memory exhaustion
- AsyncAwsS3 now disregards top-level directories from listings
- LocalAdapter now deals with file deletions during directory listings gracefully.

### Added

- DirectoryListing now supports correct phpstan for map and filter methods. 
- FTP/SFTP added config option to detect the mime-type using the path alone (prevents a read)
- SFTP now supports PuTTY style private keys
- 

## 3.12.3 - 2023-02-18

### Fixed

- [Google Cloud Storage] Fixed ACL error for uniform bucker ACL copy operations.
- 
- ## 3.12.2 - 2023-01-19

### Fixed

- [AWS S3] Corrected param order for doesObjectExistV2 call

## 3.12.1 - 2023-01-06

### Fixed

- [AWS S3] Use doesObjectExistV2 to prevent false positive respomnses.

## 3.12.0 - 2022-12-20

### Added

- [Core] Chained public URL generation strategy

### Fixed

- [WebDAV] Handle cases where the content listing returns entries with URL prefixes.
- [Local] Ensure correct implicit root creations happens on windows.
- [ZipArchive] Fix incorrect zip contents listing for top-level directory.

## 3.11.0 - 2022-12-02

### Added

- [Google Cloud Storage] Added `UniformBucketLevelAccessVisibility` to allow buckets with uniform bucket-level access policies.

## 3.10.4 - 2022-11-26

### Changed

- [I became a dad, meet Tim](https://twitter.com/frankdejonge/status/1594966175108177921)

### Fixed

- [PathPrefixing] ensure `checksum` and `temporaryUrl` are prefixed
- [WebDAV] ensure directory creation uses trailing slashes for paths

## 3.10.3 - 2022-11-14

### Fixed

- [Local] Handle checksum errors without message (#1590)

## 3.10.2 - 2022-10-25

### Fixed

- [Filesystem] Ensure adapter is used for exposing temporary URLs.

## 3.10.1 - 2022-10-21

### Fixed

- [Filesystem] Added missing constructor argument to allow temporary URL generator injection.

## 3.10.0 - 2022-10-21

### Added

- [Filesystem] added `temporaryUrl` method
- [AsyncAWS] added `temporaryUrl` method
- [AWS S3] added `temporaryUrl` method
- [Azure Blob Storage] added `temporaryUrl` method
- [MountManager] added `temporaryUrl` method
- [Google Cloud Storage] added `temporaryUrl` method
- [ReadOnly] added `temporaryUrl` method
- [PathPrefixing] added `temporaryUrl` method

## 3.9.0 - 2022-10-18

### Added

- [Filesystem] Added ability to inject custom public URL generator into a filesystem.
- [MountManager] added `checksum` and `publicUrl` methods
- [ZipArchive] Do not prefix directories when creating/reading an archive
- [ShardedPrefixPublicUrlGenerator] added url generator strategy that distributes over a list of prefixes

## 3.8.0 - 2022-10-18

### Added

- [ChecksumAlgoIsNotSupported] Exception to indicate a checksum is not supported by the checksum provider, filesystem will fall back to ad-hoc generation.

## 3.7.0 - 2022-10-17

### Added

- [Filesystem] added `checksum` method
- [AWS S3] added `checksum` method
- [Async S3] added `checksum` method
- [Google Cloud Storage] added `checksum` method
- [Azure Blob Storage] added `checksum` method

## 3.6.0 - 2022-10-13

### Added

- [Filesystem] Added public url method
- [Azure Blob Storage] Added public url method
- [AWS S3] Added public url method
- [Async S3] Added public url method
- [GCS] Added public url method
- [WebDAV] Added public url method
- [ReadOnly] Added public url method
- [PathPrefixing] Added public url method

## 3.5.3 - 2022-09-23

### Fixed

- [SFTP] Account for missing "type" field in metadata result.

## 3.5.2 - 2022-09-23

### Fixed

- [SFTP v2/v3] Fixed possible race-condition during directory creation leading to false failures.

## 3.5.1 - 2022-09-18

### Fixed

- [WebDAV] Strip directory prefix in `createDirectory` to prevent double prefixing in `directoryExists`.

## 3.5.0 - 2022-09-17

### Added

- [AWS S3] Allow specifying visibility on move and copy

## 3.4.0 - 2022-09-15

### Added

- Added FTP configuration option useRawListOptions (null|false|true).
- UnableToListContents exception was added to uniformly represent content listing exceptions.

### Fixed

- [FTP] Don't use raw list options for FileZilla FTP servers ([#1553](https://github.com/thephpleague/flysystem/pull/1553))
- [WebDAV] Correct path formatting for move and copy operations ([#1552](https://github.com/thephpleague/flysystem/pull/1552))

## 3.3.0 - 2022-09-09

### Added

- StaticInMemoryAdapterRegistry contributed by @kbond
- ReadonlyFilesystemAdapter contributed by @kbond
- PathPrefixedAdapter contributed by @shyim

### Fixed

- WebDAV prefix is now encoded and the dir is not required to be pre-created ([#1533](https://github.com/thephpleague/flysystem/pull/1533))

## 3.2.1 - 2022-08-14

### Fixed

- [ZipArchive] reverted regression introduced in [#1525](https://github.com/thephpleague/flysystem/pull/1525)

## 3.2.0 - 2022-07-26

### Added

- [AwsS3V3] Added configuration options for forwarded options, multipart upload configuration, and metadata fields.

### Fixes

- [ZipArchive] delete top-level directory when deleting directories.
- [AwsS3V3] add `ChecksumAlgorithm` to forwarded options.
- [AwsS3V3] add `ContentMD5` to forwarded options.
- [AwsS3V3] made forwarded options and metadata fields configurable.
- [SftpV3] upgrade minimum version, PHP 8 and the lowest version fails to authenticate.

## 3.1.1 - 2022-07-18

- [AwsS3V3] Corrected exception type (#1524)

## 3.1.0 - 2022-06-29

- Added option for the Local adapter to create the root directory only on the first mutating (write/copy/move) action.

## 3.0.23 - 2022-06-29

- Added reasons for exceptions for all adapters that were missing previous exception messages.

## 3.0.22 - 2022-06-29

- [AwsS3V3] Added reasons for exceptions
- [AwsS3V3] Use ListObjectsV2 instead of ListObjects

## 3.0.21 - 2022-06-12

- [AwsS3V3] Use ListObjectsV2 instead of ListObjects

## 3.0.20 - 2022-05-25

### Fixed

- [Core] Fix deprecated ${var} string interpolation patterns (#1470)

## 3.0.19 - 2022-05-03

### Fixed

- [FTP] Turn errors into proper exceptions when resolving the connection root (#1460)

## 3.0.18 - 2022-04-25

### Fixed

- [SFTP v3] Fix retries (#1451)

## 3.0.17 - 2022-04-14

### Fixed

- [SFTP v2] Avoid type errors when public key is not retrieved (#1446)
- [SFTP v3] Avoid type errors when public key is not retrieved (#1446)

## 3.0.16 - 2022-04-11

### Fixed

- [Local] fall back to extension lookups when the mime-type comes up as inconclusive.

## 3.0.15 - 2022-04-08

### Fixed

- [GCS] Allow setting upload metadata
- [GCS] Allow setting contentType, or resolve it
- [SFTP v2+v3] Delete top-level directory too.

## 3.0.14 - 2022-04-06

### Added

- [InMemory] allow to set a last-updated time (#1438)
- [SFTP V3] allow configuring preferred algo's (#1440)

## 3.0.13 - 2022-04-02

### Fixed

- [AWS S3 V3] Do not return top-level directory when listing that same directory

## 3.0.12 - 2022-03-12

### Fixed

- [SFTP V3] Fix issue where listing is false.
- [Async AWS S3] Cosmetic fix for directory prefixing.

## 3.0.11 - 2022-03-04

### Fixed

- [AWS S3] Use globally configured options.

## 3.0.10 - 2022-02-26

### Fixed

- [AWS S3] fix detecting directories that only contain other directories but no files.
- [AWS S3] when checking for directory existence, limit the result set (perf)
- [AWS S3] throw interface exception when failing to delete directory
- [Async AWS S3] when checking for directory existence, limit the result set (perf)

## 3.0.9 - 2022-02-22

### Fixed

- [AWS S3] support setting an ACL as a direct option instead of using visibility.

## 3.0.8 - 2022-02-16

### Fixed

- [AWS S3] Set ContentType when mime-type config option is set during writes, like in v1.

## 3.0.7 - 2022-02-14

### Fixed

- [WebDAV] added missing composer.json for sub-split

## 3.0.6 - 2022-02-14

### Added

- [WebDAV] new adapter added

### Fixed

- [Core] Trim slashed uniformly in the attribute classes.
- [Core] Uniformly use directory_visibility over visibility for directory usage.
- [FTP] export-ignore the test case.

## 3.0.5 - 2022-02-12

### Added

- [AzureBlobStorage] New adapter added

### Fixed

- [AsyncAwsS3] Make EXTRA_METADATA_FIELDS protected to prevent error when extending the class

## 3.0.4 - 2022-02-10

### Fixed

- [FTP] Do not require setting of the root directory, use '' by default.

## 3.0.3 - 2022-01-31

### Fixed

- [FTP] Made connection resolving lazy again (#1414)

## 3.0.2 - 2022-01-30

### Fixes

* [FTP] Support relative or empty connection root directories (#1410)

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

## 2.5.0 - 2022-09-17

### Added

- [AWS S3] Allow specifying visibility on move and copy

## 2.4.5 - 2022-04-25

- [SFTP v3] Fix retries (#1451)

## 2.4.4 - 2022-04-14

### Fixed

- [SFTP v2] Avoid type errors when public key is not retrieved (#1446)
- [SFTP v3] Avoid type errors when public key is not retrieved (#1446)

## 2.4.3 - 2022-02-16

### Fixed

- [AWS S3] Set ContentType when mime-type config option is set during writes, like in v1.

## 2.4.2 - 2022-01-31

### Fixed

- [FTP] Made connection resolving lazy again (#1414)

## 2.4.1 - 2022-01-30

### Fixed

- [FTP] Fix relative connection root handling

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
