<?php

namespace DreamFactory\Core\File\Components;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Utility\FileUtilities;

class SFTPFileSystem extends FTPFileSystem
{
    protected function setAdapter($config)
    {
        $this->adapter = new DFSftpAdapter($config);
    }

    /**
     * @param array  $folder
     * @param string $localizer
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function normalizeFolderInfo(array & $folder, $localizer)
    {
        if(empty(array_get($folder, 'type')) && !empty(array_get($folder, 'path'))){
            $folder['type'] = 'dir';
        }
        if (strtolower(array_get($folder, 'type')) !== 'dir') {
            throw new InternalServerErrorException('Fatal error. Invalid folder info provided for normalization.');
        }

        $path = array_get($folder, 'path');
        $folder['type'] = 'folder';
        $folder['path'] = FileUtilities::fixFolderPath($path);
        $folder['name'] = trim(substr($path, strlen($localizer)), '/');
        $folder['last_modified'] = gmdate('D, d M Y H:i:s \G\M\T', array_get($folder, 'timestamp', 0));
        unset($folder['timestamp']);
    }

    /**
     * @param array  $file
     * @param string $localizer
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function normalizeFileInfo(array &$file, $localizer)
    {
        if (strtolower(array_get($file, 'type')) !== 'file') {
            throw new InternalServerErrorException('Fatal error. Invalid file info provided for normalization.');
        }

        unset($file['visibility']);
        $path = array_get($file, 'path');
        $file['name'] = trim(substr($path, strlen($localizer)), '/');
        $file['last_modified'] = gmdate('D, d M Y H:i:s \G\M\T', array_get($file, 'timestamp', 0));
        $file['content_type'] = array_get($this->adapter->getMimetype($path), 'mimetype');
        $file['content_length'] = array_get($file, 'size');
        unset($file['size'], $file['timestamp']);
    }
}