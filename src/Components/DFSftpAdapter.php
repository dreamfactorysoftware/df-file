<?php

namespace DreamFactory\Core\File\Components;

use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\Util;

class DFSftpAdapter extends SftpAdapter
{
    public function __construct(array $config)
    {        
        parent::__construct(SftpConnectionProvider::fromArray($config), $config['root']);
    }
}