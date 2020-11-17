<?php

declare(strict_types=1);

namespace League\Flysystem\ZipArchive;

use ZipArchive;

class StubZipArchive extends ZipArchive
{
    /**
     * @var bool
     */
    private $failNextDirectoryCreation = false;

    /**
     * @var bool
     */
    private $failNextWrite = false;

    /**
     * @var false
     */
    private $failNextDeleteName = false;

    /**
     * @var false
     */
    private $failWhenSettingVisibility = false;

    /**
     * @var bool
     */
    private $failWhenDeletingAnIndex = false;

    public function failNextDirectoryCreation(): void
    {
        $this->failNextDirectoryCreation = true;
    }

    public function addEmptyDir($dirname)
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

    public function addFromString($localname, $contents)
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
     * @return bool|resource
     */
    public function deleteName($name)
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

    public function setExternalAttributesName($name, $opsys, $attr, $flags = NULL): bool
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

    public function deleteIndex($index)
    {
        if ($this->failWhenDeletingAnIndex) {
            $this->failWhenDeletingAnIndex = false;

            return false;
        }

        return parent::deleteIndex($index);
    }
}
