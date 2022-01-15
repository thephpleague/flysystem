<?php

use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\StorageAttributes;

include_once __DIR__ . '/tools.php';

if ( ! isset($argv[1])) {
    panic('No base version provided');
}

$mainVersion = $argv[1];

write_line("☝️ Setting all flysystem constraints to {$mainVersion}.");

$filesystem = new Filesystem(new LocalFilesystemAdapter(__DIR__ . '/../'));

/** @var string[] $otherComposers */
$composerFiles = $filesystem->listContents('src', true)
    ->filter(function (StorageAttributes $item) {
        return $item->isFile();
    })
    ->filter(function (FileAttributes $item) {
        return substr($item->path(), -5) === '.json';
    })
    ->map(function (FileAttributes $item) {
        return $item->path();
    })
    ->toArray();

foreach ($composerFiles as $composerFile) {
    $contents = $filesystem->read($composerFile);
    $mainVersionRegex = preg_quote($mainVersion, '~');
    $updated = preg_replace('~("league/flysystem": "\\^[a-zA-Z0-9\\.-]+")~ms', '"league/flysystem": "^' . $mainVersion . '"', $contents);
    $filesystem->write($composerFile, $updated);
}
