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
        $config['privateKey'] = $config['private_key'] ?? null;
        unset($config['private_key']);
        $config['hostFingerprint'] = $config['host_fingerprint'] ?? null;
        unset($config['host_fingerprint']);
        $passphrase = $config['passphrase'] ?? null;
        unset($config['passphrase']);
        $config['passphrase'] = ($passphrase === '') ? null : $passphrase;
        $this->adapter = new DFSftpAdapter($config);
    }
}