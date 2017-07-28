<?php


namespace DreamFactory\Core\File\Services;

use DreamFactory\Core\File\Components\FTPFileSystem;
use DreamFactory\Core\Exceptions\InternalServerErrorException;

class FTPFileService extends RemoteFileService
{
    protected function setDriver($config)
    {
        $this->container = array_get($config, 'container');
        $config['root'] = $this->container;

        if (empty($this->container)) {
            throw new InternalServerErrorException(
                'FTP file service root folder not specified. Please check configuration for file service - ' .
                $this->name
            );
        }

        $this->driver = new FTPFileSystem($config);
    }
}