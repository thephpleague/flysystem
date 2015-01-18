# Changelog

## 1.0.0 - UNRELEASED

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
