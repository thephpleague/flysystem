<?php

use Sabre\DAV\FS\Directory;
use Sabre\DAV\Server;

include __DIR__ . '/../../../vendor/autoload.php';

error_reporting(E_ALL ^ E_DEPRECATED);

$rootPath = __DIR__ . '/data';

if ( ! is_dir($rootPath)) {
    mkdir($rootPath);
}

$rootDirectory = new Directory($rootPath);
$server = new Server($rootDirectory);
$server->addPlugin(new Sabre\DAV\Browser\Plugin());

if (strpos($_SERVER['REQUEST_URI'], 'unknown-mime-type.md5') === false) {
    $guesser = new Sabre\DAV\Browser\GuessContentType();
    $guesser->extensionMap['svg'] = 'image/svg+xml';
    $server->addPlugin($guesser);
}

$server->start();
