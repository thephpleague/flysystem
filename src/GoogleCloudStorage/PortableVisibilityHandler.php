<?php

declare(strict_types=1);

namespace League\Flysystem\GoogleCloudStorage;

use Google\Cloud\Core\Exception\NotFoundException;
use Google\Cloud\Storage\Acl;
use Google\Cloud\Storage\StorageObject;
use League\Flysystem\Visibility;

class PortableVisibilityHandler implements VisibilityHandler
{
    public const ACL_PUBLIC_READ = 'publicRead';
    public const ACL_AUTHENTICATED_READ = 'authenticatedRead';
    public const ACL_PRIVATE = 'private';
    public const ACL_PROJECT_PRIVATE = 'projectPrivate';

    /**
     * @var string
     */
    private $entity;

    /**
     * @var string
     */
    private $predefinedPublicAcl;

    /**
     * @var string
     */
    private $predefinedPrivateAcl;

    public function __construct(
        string $entity = 'allUsers',
        string $predefinedPublicAcl = self::ACL_PUBLIC_READ,
        string $predefinedPrivateAcl = self::ACL_PROJECT_PRIVATE
    ) {
        $this->entity = $entity;
        $this->predefinedPublicAcl = $predefinedPublicAcl;
        $this->predefinedPrivateAcl = $predefinedPrivateAcl;
    }

    public function setVisibility(StorageObject $object, string $visibility): void
    {
        if ($visibility === Visibility::PRIVATE) {
            $object->acl()->delete($this->entity);
        } elseif ($visibility === Visibility::PUBLIC) {
            $object->acl()->update($this->entity, Acl::ROLE_READER);
        }
    }

    public function determineVisibility(StorageObject $object): string
    {
        try {
            $acl = $object->acl()->get(['entity' => 'allUsers']);
        } catch (NotFoundException $exception) {
            return Visibility::PRIVATE;
        }

        return $acl['role'] === Acl::ROLE_READER
            ? Visibility::PUBLIC
            : Visibility::PRIVATE;
    }

    public function visibilityToPredefinedAcl(string $visibility): string
    {
        if ($visibility === Visibility::PUBLIC) {
            return $this->predefinedPublicAcl;
        }

        return $this->predefinedPrivateAcl;
    }
}
