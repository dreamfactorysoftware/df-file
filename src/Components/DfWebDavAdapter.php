<?php

namespace DreamFactory\Core\File\Components;

use League\Flysystem\WebDAV\WebDAVAdapter;
use League\Flysystem\Config;

class DfWebDavAdapter extends WebDAVAdapter
{
    /**
     * Ensure a directory exists.
     *
     * @param string $dirname
     */
    public function ensureDirectory($dirname)
    {
        if ( ! empty($dirname) && ! $this->has($dirname)) {
            $this->createDir($dirname, new Config());
        }
    }
}