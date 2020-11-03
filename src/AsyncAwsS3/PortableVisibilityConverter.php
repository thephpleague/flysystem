<?php

declare(strict_types=1);

namespace League\Flysystem\AsyncAwsS3;

use AsyncAws\S3\ValueObject\Grant;
use League\Flysystem\Visibility;

class PortableVisibilityConverter implements VisibilityConverter
{
    private const PUBLIC_GRANTEE_URI = 'http://acs.amazonaws.com/groups/global/AllUsers';
    private const PUBLIC_GRANTS_PERMISSION = 'READ';
    private const PUBLIC_ACL = 'public-read';
    private const PRIVATE_ACL = 'private';

    /**
     * @var string
     */
    private $defaultForDirectories;

    public function __construct(string $defaultForDirectories = Visibility::PUBLIC)
    {
        $this->defaultForDirectories = $defaultForDirectories;
    }

    public function visibilityToAcl(string $visibility): string
    {
        if (Visibility::PUBLIC === $visibility) {
            return self::PUBLIC_ACL;
        }

        return self::PRIVATE_ACL;
    }

    /**
     * @param Grant[] $grants
     */
    public function aclToVisibility(array $grants): string
    {
        foreach ($grants as $grant) {
            if (null === $grantee = $grant->getGrantee()) {
                continue;
            }
            $granteeUri = $grantee->getURI();
            $permission = $grant->getPermission();

            if (self::PUBLIC_GRANTEE_URI === $granteeUri && self::PUBLIC_GRANTS_PERMISSION === $permission) {
                return Visibility::PUBLIC;
            }
        }

        return Visibility::PRIVATE;
    }

    public function defaultForDirectories(): string
    {
        return $this->defaultForDirectories;
    }
}
