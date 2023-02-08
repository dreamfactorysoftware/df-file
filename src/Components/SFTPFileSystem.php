<?php

namespace DreamFactory\Core\File\Components;

use DreamFactory\Core\Exceptions\NotFoundException;
use Illuminate\Support\Arr;

class SFTPFileSystem extends FTPFileSystem
{
    /**
     * {@inheritdoc}
     */
    protected function setAdapter($config)
    {
        $config['privateKey'] = $config['private_key'];
        unset($config['private_key']);
        $config['hostFingerprint'] = $config['host_fingerprint'];
        unset($config['host_fingerprint']);
        $this->adapter = new DFSftpAdapter($config);
    }
}