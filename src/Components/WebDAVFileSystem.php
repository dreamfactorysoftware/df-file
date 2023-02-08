<?php

namespace DreamFactory\Core\File\Components;

use Sabre\DAV\Client;
use Illuminate\Support\Arr;

class WebDAVFileSystem extends BaseFlysystem
{
    /**
     * {@inheritdoc}
     */
    protected function setAdapter($config)
    {
        $this->adapter = new DfWebDavAdapter(new Client($config), Arr::get($config, 'path_prefix'));
    }

}