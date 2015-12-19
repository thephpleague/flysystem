# Changelog

## 1.0.16 - 2015-12-19

### Fixed

* [Ftp::isConnected] PHP warnings are prevented by improving the connection check.
* [Ftp::listContents] Recursive listings not use the `R` flag instead of the function param.
* [Ftp::listContents] The `*` character is now properly escaped.
* [Ftp::getMetadata] The `*` character is now properly escaped.
* [Ftp] An `ignorePassiveAddress` option has been added to allow NAS FTP servers to work.
* [Util] Mimetype `application/x-empty` is not treated as `text/plain` and will fall back to extension based mimetype checks.
* [Local] Unreadable files no longer cause a Fatal error, they're not a catchable exception.

## 1.0.15 - 2015-10-01

### Fixed

* [Util::emulateDirectories] Now emulates correctly when a mix of files and directories are returned.

## 1.0.14 - 2015-09-28

### Added

* [Adapter\Local] Now has configurable file and directory permissions.

## 1.0.13 - 2015-09-20

### Fixed

* [Adapter\Ftp] Now tries to reconnect when a connection is dropped.

## 1.0.12 - 2015-09-05

### Fixed

* [Util::pathinfo] Now checks for existence of the dirname key, it's missing in some PHP versions.

## 1.0.11 - 2015-07-18

### Fixed

* [Adapter\Local::deleteDir] Now removes up links correctly.

## 1.0.10 - 2015-07-21

### Fixed

* [Filesystem::listContents] The implementation is clearer now and works more reliably for windows users.

## 1.0.9 - 2015-07-13

### Fixed

* [Filesystem::listContents] This function now uses DIRECTORY_SEPARATOR when the local adapter is used. 

## 1.0.8 - 2015-07-12

### Altered

* [Local::deleteDir] This function now uses the correct (reversed) iterator instead of relying in listContents.

### Added

* [Local] The Local adapter now has the ability to skip links using Local::SKIP_LINKS as the third constructor argument.

## 1.0.7 - 2015-07-11

### Fixed

* [Filesystem] Fixed the handling of directories named "0".

## 1.0.6 - 2015-07-08

### Fixed

* [Adapter\Local] Directories are no longer created with the 0777 permissions which is unsafe for shared hosting environments.

## 1.0.5 - 2015-07-08

### Fixed

* [Filesystem::listContent] Emulated directories didn't respect the natural sorting, this is now corrected in the listContents method.
* [Filesystem::listContents] The result excess from listing calls wasn't filtered strict enough, this is now improved.

### Added

* [Handler] Added getter for the Filesystem.
* [Handler] Now allows plugins calls.

## 1.0.4 - 2015-06-07

### Fixed

* [Adapter\Ftp] Now handles windows FTP servers.
* [Adapter\Local] Symlinks are now explicitly not supported, this was previously broken.
* [Adapter\Ftp] Detecting whether a path is a directory or not is more reliable.
* [Adapter\SynologyFtp] Has been renamed to Ftpd (The original class still exists for BC).
* [Filesystem] Not uses `getAdapter` internally to aid extension.
* [Adapter\Local] Now uses `umask` when creating directories to make it more reliable.
* [Misc] Coding style fixes.

## 1.0.3 - 2015-03-29

### Fixed

* #429: Handle FTP filenames with leading spaces.
* #418: Handle FTP filenames with dot prefixes.
* #427: Path normalising edge case resolved.

## 1.0.2 2015-03-10

### Altered

* [Adapter\Local] Again allows read only dirs to be the adapter's root.

## 1.0.1 - 2015-01-23

### Fixed

* Re-added missing metadata from pathinfo to `getMetadata` calls.

## 1.0.0 - 2015-01-19

### Removed

* Adapters moved into their own repo's: AwsS3, Dropbox, GridFS, Rackspace
* [Filesystem] Caching is removed and moved into it's own repo as an adapter decorator.

### Fixed

* [FilesystemInterface] This interface is now no longer related to the AdapterInterface and now correctly specifies return type.
* [AdapterInterface] The adapter interface now consistently specifies return type.

### Altered

* [AbstractAdapter / Polyfills] Polyfill methods from the AbstractAdapter are now moved to their own traits and only included in adapters that need them.

## 0.5.12 - 2014-11-05

### Fixed

* [Cache] Cache contents is now in control over what's cached instead of the implicit controle the adapters had.

## 0.5.11 - 2014-11-05

### Fixed

* [AwsS3] Removed raw response from response array
* [Cache] Ensure cache response is JSON formatted and has the correct entries.

## 0.5.10 - 2014-10-28

### Fixed

* [AwsS3] Contents supplied during AwsS3::write is now cached like all the other adapters. (Very minor chance of this happening)
* [AwsS] Detached stream from guzzle response to prevent it from closing on EntityBody destruction.
* [Util] Paths with directory names or file names with double dots are now allowed.
* [Cache:Noop] Added missing readStream method.

## 0.5.9 - 2014-10-18

### Fixed

* [AwsS3] CacheControl write option is now correctly mapped.
* [AwsS3] writeStream now properly detects Body type which resulted in cache corruption: c7246e3341135baad16180760ece3967da7a44f3

## 0.5.8 - 2014-10-17

### Fixed

* [Rackspace] Path prefixing done twice when retrieving meta-data.
* [Core] Finfo is only used to determine mime-type when available.
* [AwsS3] Previously set ACL is now respected in rename and copy.

### Added

* Stash cache adapter.


---

## 0.5.7 - 2014-09-16

### Fixed

* Path prefixing would done twice for rackspace when using streams for writes or updates.

---

## 0.5.6 - 2014-09-09

### Added

- Copy Adapter

### Fixed

- Dropbox path normalisation.

---
