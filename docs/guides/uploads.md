---
layout: default
permalink: /docs/guides/uploads/
redirect_from: /recipes/
title: Handling Uploads
---

## Plain PHP Upload

```php
$stream = fopen($_FILES[$uploadname]['tmp_name'], 'r+');
$filesystem->writeStream(
    'uploads/'.$_FILES[$uploadname]['name'],
    $stream
);
fclose($stream);
```

## Laravel 5 - DI

```php
<?php

namespace App\Http\Controllers;

use League\Flysystem\FilesystemInterface;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Controller;

class UploadController extends Controller
{
    public function store(
        Request $request,
        FilesystemInterface $filesystem
    ) {
        $file = $request->file('upload');
        $stream = fopen($file->getRealPath(), 'r+');
        $filesystem->writeStream(
            'uploads/'.$file->getClientOriginalName(),
            $stream
        );
        fclose($stream);
    }
}
```

## Symfony Upload

```php
/** @var Symfony\Component\HttpFoundation\Request $request */
/** @var Symfony\Component\HttpFoundation\File\UploadedFile $file */
$file = $request->files->get($uploadname);

if ($file->isValid()) {
    $stream = fopen($file->getRealPath(), 'r+');
    $filesystem->writeStream('uploads/'.$file->getClientOriginalName(), $stream);
    fclose($stream);
}
```

## Laravel 4/5 - Static-Access Proxy

```php
$file = Request::file($uploadname);

if ($file->isValid()) {
    $stream = fopen($file->getRealPath(), 'r+');
    $filesystem->writeStream('uploads/'.$file->getClientOriginalName(), $stream);
    fclose($stream);
}
```

## Yii 2 Upload

```php
<?php

namespace app\controllers;

use yii\web\Controller;
use yii\web\UploadedFile;

class FileController extends Controller
{
    public function actionUpload()
    {
        $file = UploadedFile::getInstanceByName($uploadname);
        
        if ($file->error === UPLOAD_ERR_OK) {
            $stream = fopen($file->tempName, 'r+');
            $filesystem->writeStream('uploads/'.$file->name, $stream);
            fclose($stream);
        }
    }
}
```
