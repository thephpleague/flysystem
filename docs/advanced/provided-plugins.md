---
layout: default
permalink: /docs/advanced/provided-plugins/
title: Provided Plugins
---

## List paths.

This requires the `League\Flysystem\Plugin\ListPaths` plugin.


```php
$filesystem->addPlugin(new ListPaths());
$paths = $filesystem->listPaths($path, $recursive);

foreach ($paths as $path) {
    echo $path;
}
```

## List with ensured presence of specific metadata.

This requires the `League\Flysystem\Plugin\ListWith` plugin.

```php
$filesystem->addPlugin(new ListWith);
$listing = $filesystem->listWith(['mimetype', 'size', 'timestamp'], 'optional/path/to/dir', true);

foreach ($listing as $object) {
    echo $object['path'].' has mimetype: '.$object['mimetype'];
}
```

## Get file into with explicit metadata.

This requires the `League\Flysystem\Plugin\GetWithMetadata` plugin.

```php
$info = $filesystem->getWithMetadata($path, ['timestamp', 'mimetype']);
echo $info['mimetype'];
echo $info['timestamp'];
```
