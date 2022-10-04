# Changelog

## 1.1.10 - 2022-10-04

### Fixed

- [FTP] Prevented type-error during network failure in `ftp_raw` calls.

## 1.1.9 - 2021-12-09

- [Core] prevent `strlen` from receiving `NULL`.

## 1.1.8 - 2021-11-28

### Fixed

- [FTP] Detect PHP an FTP connection by either is_resource OR it being an instance of `FTP\Connection` (PHP 8.1)

## 1.1.7 - 2021-11-25

### Fixed

- [FTP] Windows detection on installations that produce lines with whitespaces (#1384)

## 1.1.6 - 2021-11-21

### Fixed

- [FTP] Listing contents required escaping for special characters (caused missing contents and failure of directory deletes)

## 1.1.5 - 2021-08-17

### Fixed

* [FTP] Do not fail when setting a connection to UTF-8 when it is already on UTF-8.

## 1.1.4 - 2021-05-22

### Fixed

- [Core] Whitespace normalization now no longer strips funky whitespace but throws an exception.

## 1.1.3 - 2020-08-23

### Fixes

* Prevent closed resources from being passed.
* Allow FTP to use paths with special characters: "[]{}*"

## 1.1.2 - 2020-08-18

### Changes

* Always resolve mime-types
* Enable directory last_modified key for FTP unix listings

## 1.1.1 - 2020-08-12

### Fixes

* Make sure MimeType::detectByFilename detection falls back to `text/plain`, like before.

## 1.1.0 - 2020-08-09

### Changes

* Minimum PHP version is now 7.2.5
* MimeType now uses league/mime-type-detection
* Added (internal) League\Flysystem\Util\MimeType::useDetector to change mime-type resolving.

## 1.0.70 - 2020-07-26

### Fixes

* Local::update now also updates permissions.

## 1.0.69 - 2020-05-12

### Fixes

* Corrected the docblock return type of `getTimestamp` and enforce it in the `Filesystem`.

## 1.0.68 - 2020-05-12

### Fixes

* Added mime-types for .ico files (#1163)

## 1.0.67 - 2020-04-16

### Fixes

* Added mime-types for markdown (#1153)

## 1.0.66 - 2020-03-17

### Fixes

* Warnings from FTP are now silenced, preventing exceptions.

## 1.0.65 - 2020-03-08

- Added missing webp mime-type entry.

## 1.0.64 - 2020-02-05

- Improved performance of the connectivity check for FTP connections.

## 1.0.63 - 2020-01-04

- Introduces base exception marker and custom runtime exceptions for error cases.

## 1.0.62 - 2019-12-29

- (#1119) Made `Util::getStreamSize` account for `fstat` failure.

## 1.0.61 - 2019-12-08

- Fixed an array access issue for PHP 7.4 (#1106)

## 1.0.60 - 2019-12-08

- Fixed a PHP 7.4 issue where an array key was accessed for a non-array variable (#1105)

## 1.0.59 - 2019-12-08

- Ensure emulating directories respects a directory named "0".

## 1.0.58 - 2019-12-08

- Release lock on directories before removing them because issue with vagrant mounting.

## 1.0.57 - 2019-10-16

- Added more missing mime-types.

## 1.0.56 - 2019-10-12

- Improved packagist artifact
- Added missing mime-type.

## 1.0.55 - 2019-08-24

- Fixed metadata fetching of the existing directory in Ftpd.

## 1.0.54 - 2019-08-23

- Fixed directory creation race condition
- Prevent mime-type lookup when known in config

## 1.0.53 - 2019-06-18

- Clear stat cache before getting file metadata.

## 1.0.52 - 2019-05-20

- Correcting mimetype for CSV files according to latest RFC (https://tools.ietf.org/html/rfc7111).
- Prevent warnings for `file_get_contents` calls without `has` calls.

## 1.0.51 - 2019-03-30

- [Ftp::listContents] Added support to return 'timestamp' attribute. Note that accuracy is limited
  due to limitations in the 'LIST' command.

## 1.0.50 - 2019-02-01

- Added option `'case_sensitive'` (default `true`) for cases like Dropbox which are not.
- Concurrency issue fixed with cache clear.

## 1.0.49 - 2018-11-24

- It's my birthday today.
- Error message for directory creation in the Local adapter has a better description with more context.

## 1.0.48 - 2018-10-15

- The MountManager now implements the FilesystemInterface.

## 1.0.47 - 2018-09-14

- Specify mimetype for .epub files

## 1.0.46 - 2018-08-22

- Return failure when copying a stream does not work instead of relying only on fclose.

## 1.0.45 - 2018-05-07

- Fixed a regression in path-derived metadata fetching.

## 1.0.44 - 2018-04-06

- Added missing file presence checks on `Filesystem::setVisibility` and `Filesystem::getSize`.
- The `Handler` types are now deprecated and will be removed in 2.0.0
- The `FilesystemInterface::get` method is now deprecated and will be removed in 2.0.0

## 1.0.43 - 2018-03-01

- Remove /docs from composer artifact.

## 1.0.42 - 2018-01-27

- Fixed FTP manual recursion.
- Various code style fixes.

## 1.0.41 - 2017-08-06

- Removed support for HHVM.

### Fixed

- Response array check mistake was corrected.

## 1.0.40 - 2017-04-28

- Made it possible to indicate an adapter can overwrite files using the write functions rather than the update ones.

## 1.0.39 - 2017-04-25

### Fixed

- Some FTP servers return the `total` of 0 when a file doesn't exist instead of saying it doesn't exist.

## 1.0.38 - 2017-04-22

### Added

- You can now optionally put the FTP adapter in `utf8`-mode by setting the `utf8` setting to `true`.

### Fixed

- Pure-FTPd now escapes the first call to rawlist too.

## 1.0.37 - 2017-03-22

### Fixed

- Space escaping for Pure-FTPd in the FTP adapter.

## 1.0.36 - 2017-03-18

### Fixed

- Ensure an FTP connection is still a resource before closing it.
- Made return values of some internal adapters consistent.
- Made 0 a valid FTP username.
- Docblock class reference fixes.
- Created a more specific exception for when a mount manage is not found (with BC).

## 1.0.35 - 2017-02-09

### Fixed

- Creating a directory in FTP checked whether a directory already existed, the check was not strict enough.

## 1.0.34 - 2017-01-30

### Fixed

- Account for a Finfo buffer error which causes an array to string conversion.
- Simplified path handling for Windows.

## 1.0.33 - 2017-01-23

### Fixed

- Path traversing possibility on Windows.

## 1.0.32 - 2016-10-19

### Fixed

- Fixed listings on windows.

## 1.0.31 - 2016-10-19

### Fixed

- Relative path resolving was too greedy.

## 1.0.30 - 2016-10-18

### Changed

- Lowered minimum PHP version to 5.5.9

## 1.0.29 - 2016-10-18

### Changed

- All FTP-like adapters now have a safe storage for usernames and passwords.

## 1.0.28 - 2016-10-07

### Fixed

- [#705] Config::has now also checks the fallback config.

## 1.0.27 - 2016-08-10

### Fixed

- [#684] The local adapter now infers the mimetype based on the extension for empty files.

## 1.0.26 - 2016-08-03

### Added

- [Filesystem] Added an option to disable asserts.

## 1.0.25 - 2016-07-18

### Improved

- [Local\Ftp] Streams opened with `fopen` now open in binary mode, which is better on Windows environments.

## 1.0.24 - 2016-06-03

### Fixed

- [Local] Creating the root directory could lead to raceconditions, which are now handled a lot nicer. Initially only
  for the constructor but now also fixed the same thing for all the write operations.


## 1.0.23 - 2016-06-03

### Altered

- Default file/directory permissions are non executable.

## 1.0.22 - 2016-04-28

### Fixed

- Regression fix, the "0" root directory is now possible again.

## 1.0.21 - 2016-04-22

### Fixed

- Explicitly return false when a `has` call receives an empty filename.
- MounManager `copy` and `move` operators now comply to the `Filesystem`'s signature.

## 1.0.20 - 2016-03-14

### Improved

- MimeType detection now falls back on extension guessing when the contents is a resource.

## 1.0.19 - 2016-03-12

### Fixed

- [Util::normalizeRelativePath] `'.'` didn't normalize to `''`, this is now fixed.

## 1.0.18 - 2016-03-07

### Fixed

- Reverted "Simplified Util::pathinfo, dirname key always exists." which had unexpected side-effects.

## 1.0.17 - 2016-02-19

### Fixed

- [Util::guessMimeType] Worked around incorrect detection of assembly mime-type. (#608)

## 1.0.16 - 2015-12-19

### Fixed

- [Ftp::isConnected] PHP warnings are prevented by improving the connection check.
- [Ftp::listContents] Recursive listings not use the `R` flag instead of the function param.
- [Ftp::listContents] The `*` character is now properly escaped.
- [Ftp::getMetadata] The `*` character is now properly escaped.
- [Ftp] An `ignorePassiveAddress` option has been added to allow NAS FTP servers to work.
- [Util] Mimetype `application/x-empty` is not treated as `text/plain` and will fall back to extension based mimetype checks.
- [Local] Unreadable files no longer cause a Fatal error, they're not a catchable exception.

## 1.0.15 - 2015-10-01

### Fixed

- [Util::emulateDirectories] Now emulates correctly when a mix of files and directories are returned.

## 1.0.14 - 2015-09-28

### Added

- [Adapter\Local] Now has configurable file and directory permissions.

## 1.0.13 - 2015-09-20

### Fixed

- [Adapter\Ftp] Now tries to reconnect when a connection is dropped.

## 1.0.12 - 2015-09-05

### Fixed

- [Util::pathinfo] Now checks for existence of the dirname key, it's missing in some PHP versions.

## 1.0.11 - 2015-07-18

### Fixed

- [Adapter\Local::deleteDir] Now removes up links correctly.

## 1.0.10 - 2015-07-21

### Fixed

- [Filesystem::listContents] The implementation is clearer now and works more reliably for windows users.

## 1.0.9 - 2015-07-13

### Fixed

- [Filesystem::listContents] This function now uses DIRECTORY_SEPARATOR when the local adapter is used.

## 1.0.8 - 2015-07-12

### Altered

- [Local::deleteDir] This function now uses the correct (reversed) iterator instead of relying in listContents.

### Added

- [Local] The Local adapter now has the ability to skip links using Local::SKIP_LINKS as the third constructor argument.

## 1.0.7 - 2015-07-11

### Fixed

- [Filesystem] Fixed the handling of directories named "0".

## 1.0.6 - 2015-07-08

### Fixed

- [Adapter\Local] Directories are no longer created with the 0777 permissions which is unsafe for shared hosting environments.

## 1.0.5 - 2015-07-08

### Fixed

- [Filesystem::listContent] Emulated directories didn't respect the natural sorting, this is now corrected in the listContents method.
- [Filesystem::listContents] The result excess from listing calls wasn't filtered strict enough, this is now improved.

### Added

- [Handler] Added getter for the Filesystem.
- [Handler] Now allows plugins calls.

## 1.0.4 - 2015-06-07

### Fixed

- [Adapter\Ftp] Now handles windows FTP servers.
- [Adapter\Local] Symlinks are now explicitly not supported, this was previously broken.
- [Adapter\Ftp] Detecting whether a path is a directory or not is more reliable.
- [Adapter\SynologyFtp] Has been renamed to Ftpd (The original class still exists for BC).
- [Filesystem] Not uses `getAdapter` internally to aid extension.
- [Adapter\Local] Now uses `umask` when creating directories to make it more reliable.
- [Misc] Coding style fixes.

## 1.0.3 - 2015-03-29

### Fixed

- #429: Handle FTP filenames with leading spaces.
- #418: Handle FTP filenames with dot prefixes.
- #427: Path normalising edge case resolved.

## 1.0.2 2015-03-10

### Changed

- [Adapter\Local] Again allows read only dirs to be the adapter's root.

## 1.0.1 - 2015-01-23

### Fixed

- Re-added missing metadata from pathinfo to `getMetadata` calls.

## 1.0.0 - 2015-01-19

### Removed

- Adapters moved into their own repo's: AwsS3, Dropbox, GridFS, Rackspace
- [Filesystem] Caching is removed and moved into it's own repo as an adapter decorator.

### Fixed

- [FilesystemInterface] This interface is now no longer related to the AdapterInterface and now correctly specifies return type.
- [AdapterInterface] The adapter interface now consistently specifies return type.

### Changed

- [AbstractAdapter / Polyfills] Polyfill methods from the AbstractAdapter are now moved to their own traits and only included in adapters that need them.

## 0.5.12 - 2014-11-05

### Fixed

- [Cache] Cache contents is now in control over what's cached instead of the implicit controle the adapters had.

## 0.5.11 - 2014-11-05

### Fixed

- [AwsS3] Removed raw response from response array
- [Cache] Ensure cache response is JSON formatted and has the correct entries.

## 0.5.10 - 2014-10-28

### Fixed

- [AwsS3] Contents supplied during AwsS3::write is now cached like all the other adapters. (Very minor chance of this happening)
- [AwsS] Detached stream from guzzle response to prevent it from closing on EntityBody destruction.
- [Util] Paths with directory names or file names with double dots are now allowed.
- [Cache:Noop] Added missing readStream method.

## 0.5.9 - 2014-10-18

### Fixed

- [AwsS3] CacheControl write option is now correctly mapped.
- [AwsS3] writeStream now properly detects Body type which resulted in cache corruption: c7246e3341135baad16180760ece3967da7a44f3

## 0.5.8 - 2014-10-17

### Fixed

- [Rackspace] Path prefixing done twice when retrieving meta-data.
- [Core] Finfo is only used to determine mime-type when available.
- [AwsS3] Previously set ACL is now respected in rename and copy.

### Added

- Stash cache adapter.

## 0.5.7 - 2014-09-16

### Fixed

- Path prefixing would done twice for rackspace when using streams for writes or updates.

## 0.5.6 - 2014-09-09

### Added

- Copy Adapter

### Fixed

- Dropbox path normalisation.
