<?php

namespace DreamFactory\Core\File\Services;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\File\Components\WebDAVFileSystem;
use Illuminate\Support\Arr;

class WebDAVFileService extends RemoteFileService
{
    protected function setDriver($config)
    {
        $this->container = Arr::get($config, 'container');

        if (empty(Arr::get($config, 'base_uri'))) {
            throw new InternalServerErrorException(
                'WebDAV base URI not specified. Please check configuration for file service - ' .
                $this->name
            );
        }

        $settings = [
            'baseUri' => Arr::get($config, 'base_uri')
        ];
        if($username = Arr::get($config, 'username', false)){
            $settings['userName'] = $username;
        }
        if($password = Arr::get($config, 'password', false)){
            $settings['password'] = $password;
        }
        if($authType = Arr::get($config, 'auth_type', false)){
            $settings['authType'] = $authType;
        }
        if($encoding = Arr::get($config, 'encoding', false)){
            $settings['encoding'] = $encoding;
        }
        if($proxy = Arr::get($config, 'proxy', false)){
            $settings['proxy'] = $proxy;
        }
        $settings['path_prefix'] = Arr::get($config, 'container');
        $this->driver = new WebDAVFileSystem($settings);
    }
}