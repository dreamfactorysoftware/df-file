<?php

namespace DreamFactory\Core\File\Components;

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
        parent::normalizeFolderInfo($folder, $localizer);
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
        parent::normalizeFileInfo($file, $localizer);
        unset($file['size'], $file['timestamp']);
    }
}