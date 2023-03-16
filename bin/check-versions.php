<?php

declare(strict_types=1);

/**
 * This script check for composer dependency incompatibilities:.
 *
 *  - All required dependencies of the extracted packages MUST be
 *    present in the main composer.json's require(-dev) section.
 *  - Dependency constraints of extracted packages may not exclude
 *    the constraints of the main package and vice versa.
 *  - The provided target release argument must be satisfiable by
 *    all the extracted packages' core dependency constraint.
 */

use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\StorageAttributes;

include_once __DIR__ . '/tools.php';

function constraint_has_conflict(string $mainConstraint, string $packageConstraint): bool
{
    $parser = new VersionParser();
    $mainConstraint = $parser->parseConstraints($mainConstraint);
    $mainLowerBound = $mainConstraint->getLowerBound()->getVersion();
    $mainUpperBound = $mainConstraint->getUpperBound()->getVersion();
    $packageConstraint = $parser->parseConstraints($packageConstraint);
    $packageLowerBound = $packageConstraint->getLowerBound()->getVersion();
    $packageUpperBound = $packageConstraint->getUpperBound()->getVersion();

    if (Comparator::compare($mainUpperBound, '<=', $packageLowerBound)) {
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

write_line("🔎 Inspecting composer dependency incompatibilities.");

$mainVersion = $argv[1];
$filesystem = new Filesystem(new LocalFilesystemAdapter(__DIR__ . '/../'));

$mainComposer = $filesystem->read('composer.json');
/** @var string[] $otherComposers */
$otherComposers = $filesystem->listContents('src', true)
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

$mainInformation = json_decode($mainComposer, true);

foreach ($otherComposers as $composerFile) {
    $information = json_decode($filesystem->read($composerFile), true);

    foreach ($information['require'] as $dependency => $constraint) {
        if (str_starts_with($dependency, 'ext-') || $dependency === 'phpseclib/phpseclib') {
            continue;
        }

        if ($dependency === 'league/flysystem') {
            if ( ! Semver::satisfies($mainVersion, $constraint)) {
                panic("Composer file {$composerFile} does not allow league/flysystem:{$mainVersion}");
            } else {
                write_line("Composer file {$composerFile} allows league/flysystem:{$mainVersion} with {$constraint}");
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

write_line("✅ Composer dependencies are looking fine.");
