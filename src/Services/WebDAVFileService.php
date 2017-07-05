<?php

namespace DreamFactory\Core\File\Services;

use DreamFactory\Core\Exceptions\InternalServerErrorException;

class WebDAVFileService extends RemoteFileService
{
    protected function setDriver($config)
    {
        $this->container = array_get($config, 'container');

        if (empty(array_get($config, 'base_uri'))) {
            throw new InternalServerErrorException(
                'WebDAV base URI not specified. Please check configuration for file service - ' .
                $this->name
            );
        }

        $this->driver = new SFTPFileSystem($config);
    }
}