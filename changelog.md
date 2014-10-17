# Changelog

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