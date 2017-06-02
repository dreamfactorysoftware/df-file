<?php

namespace DreamFactory\Core\File\Services;

use DreamFactory\Core\File\Components\LocalFileSystem;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Utility\Session;

class LocalFileService extends BaseFileService
{
    protected function setDriver($config)
    {
        $this->container = '';
        if (empty($root = array_get($config, 'container'))) {
            $root = storage_path('app');
        } elseif ('local' === $root) {
            $root = storage_path('app');
        } elseif ('logs' === $root) {
            $root = storage_path('logs');
        } else {
            //  Replace any private lookups
            Session::replaceLookups($root, true);
        }

        if (empty($root)) {
            throw new InternalServerErrorException('Local file service folder not configured.' .
                ' Please check configuration for file service - ' . $this->name . '.');
        }

        if (!is_dir($root)) {
            mkdir($root, 0775);
        }

        if (!is_dir($root)) {
            throw new InternalServerErrorException('Local file service folder mis-configured.' .
                ' Please check configuration for file service - ' . $this->name . '.');
        }

        $this->driver = new LocalFileSystem($root);
    }
}
