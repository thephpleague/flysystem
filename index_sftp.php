<?php

declare(strict_types=1);

use League\Flysystem\Filesystem;
use League\Flysystem\PHPSecLibV2\SftpConnectionProvider;
use League\Flysystem\PHPSecLibV2\SftpFilesystem;

include __DIR__ . '/vendor/autoload.php';

$filesystem = new Filesystem(new SftpFilesystem(
    new SftpConnectionProvider(
        'localhost',
        '/upload',
        'foo',
        'pass',
        2222,
        false
    )
));

$filesystem->write('some/path.txt', 'contents');

var_dump($filesystem->fileExists('path.txt'));
var_dump($filesystem->read('path.txt'));

//var_dump($sftp->getSFTPLog());
