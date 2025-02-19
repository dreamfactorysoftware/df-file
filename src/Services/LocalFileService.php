<?php

namespace DreamFactory\Core\File\Services;

use DreamFactory\Core\File\Components\LocalFileSystem;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Utility\Session;
use Illuminate\Support\Arr;

class LocalFileService extends BaseFileService
{
    public function __construct($settings = [])
    {
        self::isValidPath($settings);
        parent::__construct($settings);
    }

    protected static function isRelativePath($path)
    {
        if (0 === substr_compare(PHP_OS, 'WIN', 0, 3, true)) {
            // C:\foo\bar or \\foo\bar
            if ((1 === strpos($path, ':')) || (0 === strpos($path, DIRECTORY_SEPARATOR))) {
                return false;
            }
        } elseif (0 === strpos($path, DIRECTORY_SEPARATOR)) {
            // /foo/bar
            return false;
        }

        return true;
    }

    protected static function isValidPath($settings)
    {
        $root = Arr::get(Arr::get($settings, 'config'), 'container');
        if (is_null($root)) {
            return;
        }
        $disallowedDirs = ['/', '/bin', '/sbin', '/lib', '/lib64', '/lib32', '/libx32', '/dev', '/etc'];

        foreach ($disallowedDirs as $dir) {
            if (strpos($root . DIRECTORY_SEPARATOR, $dir . DIRECTORY_SEPARATOR) === 0) {
                throw new InternalServerErrorException('Invalid root directory: ' . $dir . ' is not allowed.');
            }
        }
    }

    protected function setDriver($config)
    {
        $this->container = '';
        $root = Arr::get($config, 'container');
        //  Replace any private lookups
        Session::replaceLookups($root, true);
        // local is the old Laravel config "disk" that may still be configured
        if (empty($root) || ('local' === $root)) {
            //df config calls storage_path() so need to add this in for managed instances.
            $root = rtrim(config('df.storage_path'), '/') . DIRECTORY_SEPARATOR . 'app';
        } elseif (self::isRelativePath($root)) {
            // df config calls storage_path() so need to add this in for managed instances.
            $root = rtrim(config('df.storage_path'), '/') . DIRECTORY_SEPARATOR . ltrim($root, '/');
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
