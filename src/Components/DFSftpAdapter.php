<?php

namespace DreamFactory\Core\File\Components;

use League\Flysystem\Sftp\SftpAdapter;
use League\Flysystem\Util;

class DFSftpAdapter extends SftpAdapter
{
    /**
     * @inheritdoc
     */
    public function getMetadata($path)
    {
        $connection = $this->getConnection();
        $info = $connection->stat($path);

        if ($info === false) {
            return false;
        }

        $result = Util::map($info, $this->statMap);
        $result['type'] = $info['type'] === NET_SFTP_TYPE_DIRECTORY ? 'dir' : 'file';
        $result['visibility'] = $info['permissions'] & $this->permPublic ? 'public' : 'private';

        $name = basename($path);
        $base = str_replace($name, null, $path);
        $base = rtrim($base, '/');

        $result['path'] = empty($base) ? $name : $base . $this->separator . $name;

        return $result;
    }
}