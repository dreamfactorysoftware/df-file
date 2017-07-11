<?php

namespace DreamFactory\Core\File\Services;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\File\Components\WebDAVFileSystem;

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

        $settings = [
            'baseUri' => array_get($config, 'base_uri')
        ];
        if($username = array_get($config, 'username', false)){
            $settings['username'] = $username;
        }
        if($password = array_get($config, 'password', false)){
            $settings['password'] = $password;
        }
        if($authType = array_get($config, 'auth_type', false)){
            $settings['auth_type'] = $authType;
        }
        if($encoding = array_get($config, 'encoding', false)){
            $settings['encoding'] = $encoding;
        }
        if($proxy = array_get($config, 'proxy', false)){
            $settings['proxy'] = $proxy;
        }
        $settings['path_prefix'] = array_get($config, 'container');
        $this->driver = new WebDAVFileSystem($settings);
    }
}