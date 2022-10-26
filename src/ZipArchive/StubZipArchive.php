<?php

declare(strict_types=1);

namespace League\Flysystem\ZipArchive;

use ZipArchive;

class StubZipArchive extends ZipArchive
{
    private bool $failNextDirectoryCreation = false;
    private bool $failNextWrite = false;
    private bool $failNextDeleteName = false;
    private bool $failWhenSettingVisibility = false;
    private bool $failWhenDeletingAnIndex = false;

    public function failNextDirectoryCreation(): void
    {
        $this->failNextDirectoryCreation = true;
    }

    /**
     * @param string $dirname
     * @param int    $flags
     *
     * @return bool
     */
    public function addEmptyDir($dirname, $flags = 0): bool
    {
        if ($this->failNextDirectoryCreation) {
            $this->failNextDirectoryCreation = false;

            return false;
        }

        return parent::addEmptyDir($dirname);
    }

    public function failNextWrite(): void
    {
        $this->failNextWrite = true;
    }

    /**
     * @param string $localname
     * @param string $contents
     * @param int    $flags
     *
     * @return bool
     */
    public function addFromString($localname, $contents, $flags = 0): bool
    {
        if ($this->failNextWrite) {
            $this->failNextWrite = false;

            return false;
        }

        return parent::addFromString($localname, $contents);
    }

    public function failNextDeleteName(): void
    {
        $this->failNextDeleteName = true;
    }

    /**
     * @return bool
     */
    public function deleteName($name): bool
    {
        if ($this->failNextDeleteName) {
            $this->failNextDeleteName = false;

            return false;
        }

        return parent::deleteName($name);
    }

    public function failWhenSettingVisibility(): void
    {
        $this->failWhenSettingVisibility = true;
    }

    public function setExternalAttributesName($name, $opsys, $attr, $flags = null): bool
    {
        if ($this->failWhenSettingVisibility) {
            $this->failWhenSettingVisibility = false;

            return false;
        }

        return parent::setExternalAttributesName($name, $opsys, $attr);
    }

    public function failWhenDeletingAnIndex(): void
    {
        $this->failWhenDeletingAnIndex = true;
    }

    public function deleteIndex($index): bool
    {
        if ($this->failWhenDeletingAnIndex) {
            $this->failWhenDeletingAnIndex = false;

            return false;
        }

        return parent::deleteIndex($index);
    }
}
