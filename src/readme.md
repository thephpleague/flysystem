## Example

```php
<?php

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystem;

$adapter = new LocalFilesystem(__DIR__.'/somewhere/');
$filesystem = new Filesystem($adapter);
$resource = tmpfile();

$filesystem->write('dir/path.txt', 'contents');
$filesystem->writeStream('dir/path.txt', $resource);

$filesystem->update('dir/path.txt', 'contents');
$filesystem->updateStream('dir/path.txt', $resource);

$filesystem->delete('dir/path.txt');

$filesystem->createDirectory('dir');
$filesystem->deleteDirectory('dir');

$lastModified = $filesystem->lastModified('path.txt');
$mimeType = $filesystem->mimeType('path.txt');
$fileSize = $filesystem->fileSize('path.txt');
```
