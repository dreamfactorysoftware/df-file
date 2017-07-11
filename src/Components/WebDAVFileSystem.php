<?php

namespace DreamFactory\Core\File\Components;

use DreamFactory\Core\Exceptions\NotFoundException;
use Sabre\DAV\Client;

class WebDAVFileSystem extends BaseFlysystem
{
    /**
     * {@inheritdoc}
     */
    protected function setAdapter($config)
    {
        $this->adapter = new DfWebDavAdapter(new Client($config), array_get($config, 'path_prefix'));
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