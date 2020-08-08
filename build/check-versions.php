<?php

declare(strict_types=1);

use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\StorageAttributes;

include_once __DIR__.'/../vendor/autoload.php';

function write_line(string $line)
{
    fwrite(STDOUT, "{$line}\n");
}

function panic(string $reason)
{
    write_line('🚨 ' . $reason);
    exit(1);
}


function constraint_has_conflict(string $mainConstraint, string $packageConstraint): bool
{
    $parser = new VersionParser();
    $mainConstraint = $parser->parseConstraints($mainConstraint);
    $mainLowerBound = $mainConstraint->getLowerBound()->getVersion();
    $mainUpperBound = $mainConstraint->getUpperBound()->getVersion();
    $packageConstraint = $parser->parseConstraints($packageConstraint);
    $packageLowerBound = $packageConstraint->getLowerBound()->getVersion();
    $packageUpperBound = $packageConstraint->getUpperBound()->getVersion();


    if ( ! Comparator::compare($mainUpperBound, '==', $packageLowerBound)) {
        return true;
    }

    if (Comparator::compare($packageUpperBound, '<=', $mainLowerBound)) {
        return true;
    }

    return false;
}

if ( ! isset($argv[1])) {
    panic('No base version provided');
}

$mainVersion = $argv[1];
$filesystem = new Filesystem(new LocalFilesystemAdapter(__DIR__.'/../'));

$mainComposer = $filesystem->read('composer.json');
/** @var string[] $otherComposers */
$otherComposers = $filesystem->listContents('src', true)
    ->filter(function(StorageAttributes $item) { return $item->isFile(); })
    ->filter(function(FileAttributes $item) { return substr($item->path(), -5) === '.json'; })
    ->map(function(FileAttributes $item) { return $item->path(); })
    ->toArray();

$mainInformation = json_decode($mainComposer, true);

foreach ($otherComposers as $composerFile) {
    $information = json_decode($filesystem->read($composerFile), true);

    foreach ($information['require'] as $dependency => $constraint) {
        if (strpos($dependency, 'ext-') === 0) {
            continue;
        }

        if ($dependency === 'league/flysystem') {
            if ( ! Semver::satisfies($mainVersion, $constraint)) {
                panic("Composer file {$composerFile} does not allow league/flysystem:{$mainVersion}");
            }
            continue;
        }

        $mainDependencyConstraint = $mainInformation['require'][$dependency]
            ?? $mainInformation['require-dev'][$dependency]
            ?? null;

        if ( ! is_string($mainDependencyConstraint)) {
            panic(
                "The main composer file does not depend on an adapter dependency.\n" .
                "Depedency {$dependency} from {$composerFile} is missing."
            );
        }

        if (constraint_has_conflict($mainDependencyConstraint, $constraint)) {
            panic(
                "Package constraints are conflicting:\n\n" .
                "Package composer file: {$composerFile}\n" .
                "Dependency name: {$dependency}\n" .
                "Main constraint: {$mainDependencyConstraint}\n" .
                "Package constraint: {$constraint}"
            );
        }
    }
}

