<?php

namespace League\Flysystem\ReadOnly;

use DateTimeInterface;
use League\Flysystem\CalculateChecksumFromStream;
use League\Flysystem\ChecksumProvider;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToGeneratePublicUrl;
use League\Flysystem\UnableToGenerateTemporaryUrl;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;


/**
 * @deprecated The "League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter" class is deprecated since Flysystem 3.0, use  "League\Flysystem\ReadOnlyFilesystem\ReadOnlyFilesystemAdapter" instead.
 */
class ReadOnlyFilesystemAdapter extends \League\Flysystem\ReadOnlyFilesystem\ReadOnlyFilesystemAdapter
{
}
