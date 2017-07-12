<?php

namespace DreamFactory\Core\File\Components;

use DreamFactory\Core\Exceptions\NotFoundException;

class SFTPFileSystem extends FTPFileSystem
{
    /**
     * {@inheritdoc}
     */
    protected function setAdapter($config)
    {
        $this->adapter = new DFSftpAdapter($config);
    }

    /**
     * {@inheritdoc}
     */
    public function getFolder($container, $path, $include_files = true, $include_folders = true, $full_tree = false)
    {
        if ($this->folderExists($container, $path)) {
            $path = rtrim($path, '/');
            $contents = $this->adapter->listContents($path, $full_tree);
            foreach ($contents as $key => $content) {
                if (strtolower(array_get($content, 'type')) === 'dir') {
                    $this->normalizeFolderInfo($content, $path);
                } else {
                    $this->normalizeFileInfo($content, $path);
                }
                $contents[$key] = $content;
            }

            return $contents;
        } else {
            throw new NotFoundException("Folder '$path' does not exist in storage.");
        }
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