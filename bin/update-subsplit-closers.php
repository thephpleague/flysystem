<?php

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

include __DIR__ . '/../vendor/autoload.php';

$filesystem = new Filesystem(new LocalFilesystemAdapter(realpath(__DIR__ . '/../')));
$subsplits = json_decode($filesystem->read('config.subsplit-publish.json'), true);
$workflowContents = $filesystem->read('bin/close-subsplit-prs.yml');

foreach ($subsplits['sub-splits'] as ['directory' => $subsplit]) {
    $workflowPath = $subsplit . '/.github/workflows/close-subsplit-prs.yaml';
    $filesystem->write($workflowPath, $workflowContents);
}
