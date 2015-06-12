<?php

namespace League\Flysystem\Adapter\Polyfill;

use League\Flysystem\AdapterInterface;

trait NotSupportingVisibilityTrait
{
    /**
     * Get the visibility of a file.
     *
     * @param string $path
     */
    public function getVisibility($path)
    {
        return ['visibility' => AdapterInterface::VISIBILITY_PUBLIC];
    }

    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     */
    public function setVisibility($path, $visibility)
    {
        if ($visibility === AdapterInterface::VISIBILITY_PUBLIC) {
            return ['visibility' => AdapterInterface::VISIBILITY_PUBLIC];
        }

        return false;
    }
}
