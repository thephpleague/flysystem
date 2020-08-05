---
layout: default
title: Mime-type Detection
permalink: /v2/docs/advanced/mime-type-detection/
---

In V2 mime-type detection was promoted to its own package, called
`league/mime-type-detection`. 

This package supplies a generic mime-type detection interface with a
`finfo` based implementation.

## Usage

If you're not using Flysystem, install the package separately:

```bash
composer require league/mime-type-detection
```

### Detectors

Finfo with extension fallback:

```php
$detector = new League\MimeTypeDetection\FinfoMimeTypeDetector();

// Detect by contents, fall back to detection by extension.
$mimeType = $detector->detectMimeType('some/path.php', 'string contents');

// Detect by contents only, no extension fallback.
$mimeType = $detector->detectMimeTypeFromBuffer('string contents');

// Detect by actual file, no extension fallback.
$mimeType = $detector->detectMimeTypeFromFile('existing/path.php');

// Only detect by extension
$mimeType = $detector->detectMimeTypeFromPath('any/path.php');
```

Extension only:

```php
$detector = new League\MimeTypeDetection\ExtensionMimeTypeDetector();

// Only detect by extension
$mimeType = $detector->detectMimeType('some/path.php', 'string contents');

// Always returns null
$mimeType = $detector->detectMimeTypeFromBuffer('string contents');

// Only detect by extension
$mimeType = $detector->detectMimeTypeFromFile('existing/path.php');

// Only detect by extension
$mimeType = $detector->detectMimeTypeFromPath('any/path.php');
```

## Extension mime-type lookup

As a fallback for `finfo` based lookup, an extension map
is used to determine the mime-type. There is an advised implementation
shipped, which is generated from information collected by the npm
package [mime-db](https://www.npmjs.com/package/mime-db).

### Provided extension maps

Generated:

```php
$map = new League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap();

// string mime-type or NULL
$mimeType = $map->lookupMimeType('png');
```

Empty:

```php
$map = new League\MimeTypeDetection\EmptyExtensionToMimeTypeMap();

// Always returns NULL
$mimeType = $map->lookupMimeType('png');
```
