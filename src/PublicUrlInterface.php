<?php


namespace League\Flysystem;


interface PublicUrlInterface
{

    public function hasPublicUrl($path);
    public function getPublicUrl($path);
}