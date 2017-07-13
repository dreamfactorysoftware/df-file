<?php

namespace DreamFactory\Core\File\Components;

use DreamFactory\Core\Contracts\FileSystemInterface;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Exceptions\NotImplementedException;
use DreamFactory\Core\Exceptions\NotFoundException;
use DreamFactory\Core\Utility\FileUtilities;
use League\Flysystem\Config;
use DreamFactory\Core\Exceptions\BadRequestException;
use Log;

abstract class BaseFlysystem implements FileSystemInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var \League\Flysystem\AdapterInterface
     */
    protected $adapter;

    /**
     * FileSystem constructor.
     *
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->setAdapter($config);
    }

    /**
     * @param array $config
     */
    protected abstract function setAdapter($config);

    /**
     * {@inheritdoc}
     */
    public function listContainers($include_properties = false)
    {
        throw new NotImplementedException('Method ' . __METHOD__ . ' not applicable for current file system.');
    }

    /**
     * {@inheritdoc}
     */
    public function containerExists($container)
    {
        throw new NotImplementedException('Method ' . __METHOD__ . ' not applicable for current file system.');
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer($container, $include_files = true, $include_folders = true, $full_tree = false)
    {
        throw new NotImplementedException('Method ' . __METHOD__ . ' not applicable for current file system.');
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerProperties($container)
    {
        throw new NotImplementedException('Method ' . __METHOD__ . ' not applicable for current file system.');
    }

    /**
     * {@inheritdoc}
     */
    public function createContainer($container, $check_exist = false)
    {
        throw new NotImplementedException('Method ' . __METHOD__ . ' not applicable for current file system.');
    }

    /**
     * {@inheritdoc}
     */
    public function createContainers($containers, $check_exist = false)
    {
        throw new NotImplementedException('Method ' . __METHOD__ . ' not applicable for current file system.');
    }

    /**
     * {@inheritdoc}
     */
    public function updateContainerProperties($container, $properties = [])
    {
        throw new NotImplementedException('Updating container properties is not applicable for current file system.');
    }

    /**
     * {@inheritdoc}
     */
    public function deleteContainer($container, $force = false)
    {
        throw new NotImplementedException('Method ' . __METHOD__ . ' not applicable for current file system.');
    }

    /**
     * {@inheritdoc}
     */
    public function deleteContainers($containers, $force = false)
    {
        throw new NotImplementedException('Method ' . __METHOD__ . ' not applicable for current file system.');
    }

    /**
     * {@inheritdoc}
     */
    public function folderExists($container, $path)
    {
        return (!$this->adapter->has($path)) ? false : true;
    }

    /**
     * {@inheritdoc}
     */
    public function getFolder($container, $path, $include_files = true, $include_folders = true, $full_tree = false)
    {
        if ($this->folderExists($container, $path)) {
            $path = rtrim($path, '/');
            $this->adapter->setRecurseManually($full_tree);
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
        if (strtolower(array_get($folder, 'type')) !== 'dir') {
            throw new InternalServerErrorException('Fatal error. Invalid folder info provided for normalization.');
        }

        $path = array_get($folder, 'path');
        $folder['path'] = FileUtilities::fixFolderPath($path);
        $folder['name'] = trim(substr($path, strlen($localizer)), '/');
        if (empty($folder['name'])) {
            $folder['name'] = basename($path);
        }
        $folder['type'] = 'folder';
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
        $timestamp = $this->adapter->getTimestamp($path);
        $file['name'] = trim(substr($path, strlen($localizer)), '/');
        if (empty($file['name'])) {
            $file['name'] = basename($path);
        }
        $file['last_modified'] = gmdate('D, d M Y H:i:s \G\M\T', array_get($timestamp, 'timestamp', 0));
        $file['content_type'] = array_get($this->adapter->getMimetype($path), 'mimetype');
        $file['content_length'] = array_get($file, 'size');
        unset($file['size']);
    }

    /**
     * {@inheritdoc}
     */
    public function getFolderProperties($container, $path)
    {
        $path = rtrim($path, '/');
        $meta = $this->adapter->getMetadata($path);
        if ($meta === false) {
            throw new NotFoundException("Specified folder '" . $path . "' not found.");
        }
        if (array_get($meta, 'type') === 'dir') {
            $this->normalizeFolderInfo($meta, $path);
            unset($meta['type'], $meta['size'], $meta['visibility']);

            return $meta;
        } else {
            throw new InternalServerErrorException('Fatal error. Invalid folder path provided.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createFolder($container, $path, $properties = [])
    {
        if (empty($path)) {
            throw new BadRequestException("Invalid empty path.");
        }
        if ($this->folderExists($container, $path)) {
            throw new BadRequestException("Folder '" . $path . "' already exists.");
        }
        $path = rtrim($path, '/');
        $result = $this->adapter->createDir($path, new Config());

        if($result === false){
            throw new InternalServerErrorException("Failed to create folder '" . $path . "'.");
        }

        $this->normalizeFolderInfo($result, $path);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function updateFolderProperties($container, $path, $properties = [])
    {
        throw new NotImplementedException('Updating folder properties is not applicable for current file system.');
    }

    /**
     * {@inheritdoc}
     */
    public function copyFolder($container, $dest_path, $src_container, $src_path, $check_exist = false)
    {
        $dest_path = rtrim($dest_path, '/');
        $src_path = rtrim($src_path, '/');
        // does this folder already exist?
        if (!$this->folderExists($src_container, $src_path)) {
            throw new NotFoundException("Folder '$src_path' does not exist.");
        }
        if ($this->folderExists($container, $dest_path)) {
            if (($check_exist)) {
                throw new BadRequestException("Folder '$dest_path' already exists.");
            }
        }
        // does this folder's parent folder exist?
        $parent = FileUtilities::getParentFolder($dest_path);
        if (!empty($parent) && (!$this->folderExists($container, $parent))) {
            throw new NotFoundException("Folder '$parent' does not exist.");
        }

        static::copyTree($src_path, $dest_path);
    }

    /**
     * @param string $src
     * @param string $dst
     */
    public function copyTree($src, $dst)
    {
        $meta = $this->adapter->getMetadata($src);
        if ($meta['type'] === 'dir' && $this->folderExists('', $src)) {
            $this->adapter->ensureDirectory($dst);
            $files = $this->adapter->listContents($src);
            foreach ($files as $file) {
                $path = array_get($file, 'path');
                $srcFile = $path;
                $dstFile = FileUtilities::fixFolderPath($dst) . basename($path);
                $this->copyTree($srcFile, $dstFile);
            }
        } elseif ($meta['type'] === 'file' && $this->fileExists('', $src)) {
            $this->adapter->copy($src, $dst);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFolder($container, $path, $force = false)
    {
        if (!$this->folderExists($container, $path)) {
            throw new NotFoundException("Folder '" . $path . "' does not exist.");
        }
        $path = rtrim($path, '/');

        if ($force) {
            $this->deleteTree($path);
        } else {
            try {
                if (!$this->adapter->deleteDir($path)) {
                    throw new InternalServerErrorException("Failed to delete folder '" . $path . "'");
                }
            } catch (\Exception $e) {
                throw new InternalServerErrorException("Directory not empty, can not delete without force option.");
            }
        }
    }

    /**
     * @param string $path
     */
    public function deleteTree($path)
    {
        $path = rtrim($path, '/');
        $meta = $this->adapter->getMetadata($path);

        if ($meta['type'] === 'dir') {
            $contents = $this->adapter->listContents($path);
            foreach ($contents as $content) {
                $this->deleteTree($content['path']);
            }
            $this->adapter->deleteDir($path);
        } elseif ($meta['type'] === 'file') {
            $this->adapter->delete($path);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFolders($container, $folders, $root = '', $force = false)
    {
        throw new NotImplementedException('Method ' . __METHOD__ . ' not applicable for current file system.');
    }

    /**
     * {@inheritdoc}
     */
    public function fileExists($container, $path)
    {
        return (!$this->adapter->has($path)) ? false : true;
    }

    /**
     * {@inheritdoc}
     */
    public function getFileContent($container, $path, $local_file = null, $content_as_base = true)
    {
        throw new NotImplementedException('Method ' . __METHOD__ . ' not applicable for current file system.');
    }

    /**
     * {@inheritdoc}
     */
    public function getFileProperties($container, $path, $include_content = false, $content_as_base = true)
    {
        $path = rtrim($path, '/');
        $meta = $this->adapter->getMetadata($path);
        if ($meta === false) {
            throw new NotFoundException("Specified file '" . $path . "' does not exist.");
        } else {
            $this->normalizeFileInfo($meta, $path);
        }

        if ($include_content) {
            $streamObj = $this->adapter->readStream($path);
            if ($streamObj !== false) {
                $stream = array_get($streamObj, 'stream');
                if (empty($stream)) {
                    throw new InternalServerErrorException('Failed to retrieve file properties.');
                }
                $contents = fread($stream, array_get($meta, 'content_length'));
                if ($content_as_base) {
                    $contents = base64_encode($contents);
                }
                $meta['content'] = $contents;
            }
        }

        unset($meta['type']);

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function streamFile($container, $path, $download = false)
    {
        try {
            $result = $this->adapter->readStream($path);
            $chunk = \Config::get('df.file_chunk_size');

            if (!empty($path) && isset($result['stream'])) {
                $file = basename($path);
                $meta = $this->adapter->getMetadata($path);
                $mimeType = array_get($this->adapter->getMimetype($path), 'mimetype');
                $timestamp = array_get($this->adapter->getTimestamp($path), 'timestamp');
                $stream = array_get($result, 'stream');

                $ext = FileUtilities::getFileExtension($file);
                $disposition = ($download) ? 'attachment; filename="' . $file . '";' : 'inline';
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', $timestamp));
                header('Content-Type: ' . $mimeType);
                header('Content-Length:' . array_get($meta, 'size'));
                if ($download || 'html' !== $ext) {
                    header('Content-Disposition: ' . $disposition);
                }
                header('Cache-Control: private'); // use this to open files directly
                header('Expires: 0');
                header('Pragma: public');
                if (empty($chunk)) {
                    print(fread($stream, array_get($meta, 'size')));
                } else {
                    while (!feof($stream) and (connection_status() == 0)) {
                        print(fread($stream, $chunk));
                        flush();
                    }
                }
            } else {
                Log::debug('Failed to stream file: ' . $path);
                $statusHeader = 'HTTP/1.1 404';
                header($statusHeader);
                header('Content-Type: text/html');
                echo 'The specified file ' . $path . ' does not exist.';
            }
        } catch (\Exception $e) {
            Log::debug('Failed to stream file: ' . $path);
            $statusHeader = 'HTTP/1.1 404';
            header($statusHeader);
            header('Content-Type: text/html');
            echo 'Could not open the specified file ' . $path;
            echo $e->getMessage();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateFileProperties($container, $path, $properties = [])
    {
        throw new NotImplementedException('Updating file properties is not applicable for current file system.');
    }

    /**
     * {@inheritdoc}
     */
    public function writeFile($container, $path, $content, $content_is_base = false, $check_exist = false)
    {
        // does this file already exist?
        if ($this->fileExists($container, $path)) {
            if (($check_exist)) {
                throw new InternalServerErrorException("File '$path' already exists.");
            }
        }
        // does this folder's parent exist?
        $parent = FileUtilities::getParentFolder($path);
        if (!empty($parent) && (!$this->folderExists($container, $parent))) {
            throw new NotFoundException("Folder '$parent' does not exist.");
        }

        if ($content_is_base) {
            $content = base64_decode($content);
        }

        $result = $this->adapter->write($path, $content, new Config());
        if (false === $result) {
            throw new InternalServerErrorException('Failed to create file.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function moveFile($container, $path, $local_path, $check_exist = false)
    {
        // does local file exist?
        if (!file_exists($local_path)) {
            throw new NotFoundException("File '$local_path' does not exist.");
        }
        // does this file already exist?
        if ($this->fileExists($container, $path)) {
            if (($check_exist)) {
                throw new BadRequestException("File '$path' already exists.");
            }
        }
        // does this file's parent folder exist?
        $parent = FileUtilities::getParentFolder($path);
        if (!empty($parent) && (!$this->folderExists($container, $parent))) {
            throw new NotFoundException("Folder '$parent' does not exist.");
        }

        $stream = fopen($local_path, 'rb');
        $this->adapter->writeStream($path, $stream, new Config());
        fclose($stream);
    }

    /**
     * {@inheritdoc}
     */
    public function copyFile($container, $dest_path, $src_container, $src_path, $check_exist = false)
    {
        // does this file already exist?
        if (!$this->fileExists($src_container, $src_path)) {
            throw new NotFoundException("File '$src_path' does not exist.");
        }
        if ($this->fileExists($container, $dest_path)) {
            if (($check_exist)) {
                throw new BadRequestException("File '$dest_path' already exists.");
            }
        }
        if (!$this->adapter->copy($src_path, $dest_path)) {
            throw new InternalServerErrorException('Failed to copy file from ' . $src_path . ' to ' . $dest_path);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFile($container, $path, $noCheck = false)
    {
        if (!$noCheck && !$this->fileExists($container, $path)) {
            throw new NotFoundException("File '$path' was not found.");
        }
        if (!$this->adapter->delete($path)) {
            throw new InternalServerErrorException('Failed to delete file.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFiles($container, $files, $root = null)
    {
        throw new NotImplementedException('Method ' . __METHOD__ . ' not applicable for current file system.');
    }

    /**
     * {@inheritdoc}
     */
    public function extractZipFile($container, $path, $zip, $clean = false, $drop_path = null)
    {
        if ($clean) {
            try {
                // clear out anything in this directory
                $this->deleteFolder($container, $path, true);
            } catch (\Exception $ex) {
                throw new InternalServerErrorException("Could not clean out existing directory $path.\n{$ex->getMessage()}");
            }
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (empty($name)) {
                continue;
            }
            if (!empty($drop_path)) {
                $name = str_ireplace($drop_path, '', $name);
            }
            $fullPathName = $path . $name;
            if ('/' === substr($fullPathName, -1)) {
                $this->createFolder($container, $fullPathName);
            } else {
                $parent = FileUtilities::getParentFolder($fullPathName);
                if (!empty($parent) && !$this->folderExists($container, $parent)) {
                    $this->createFolder($container, $parent);
                }
                $content = $zip->getFromIndex($i);
                $this->writeFile($container, $fullPathName, $content);
            }
        }

        return [
            'name' => rtrim($path, DIRECTORY_SEPARATOR),
            'path' => $path,
            'type' => 'file'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFolderAsZip($container, $path, $zip = null, $zipFileName = null, $overwrite = false)
    {
        $path = rtrim($path, '/');
        if (empty($zipFileName)) {
            $temp = basename($path);
            if (empty($temp)) {
                $temp = $container;
            }
            $tempDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $zipFileName = $tempDir . $temp . '.zip';
        }
        $needClose = false;
        if (!isset($zip)) {
            $needClose = true;
            $zip = new \ZipArchive();
            if (true !== $zip->open($zipFileName, ($overwrite ? \ZipArchive::OVERWRITE : \ZipArchive::CREATE))) {
                throw new InternalServerErrorException("Can not create zip file for directory '$path'.");
            }
        }
        $this->addTreeToZip($zip, $path);
        if ($needClose) {
            $zip->close();
        }

        return $zipFileName;
    }

    /**
     * @param \ZipArchive $zip
     * @param string $path
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     * @throws \Exception
     */
    public function addTreeToZip($zip, $path)
    {
        $path = rtrim($path, '/');
        if ($this->folderExists('', $path)) {
            $files = $this->adapter->listContents($path);
            if (empty($files)) {
                $newPath = str_replace(DIRECTORY_SEPARATOR, '/', $path);
                if (!$zip->addEmptyDir($newPath)) {
                    throw new \Exception("Can not include folder '$newPath' in zip file.");
                }

                return;
            }
            foreach ($files as $file) {
                if ($file['type'] === 'dir') {
                    static::addTreeToZip($zip, $file['path']);
                } else if ($file['type'] === 'file') {
                    $newPath = str_replace(DIRECTORY_SEPARATOR, '/', $file['path']);
                    $fileObj = $this->adapter->read($newPath);
                    if (!empty($fileObj) && isset($fileObj['contents'])) {
                        if (!$zip->addFromString($file['path'], $fileObj['contents'])) {
                            throw new \Exception("Can not include file '$newPath' in zip file.");
                        }
                    } else {
                        throw new InternalServerErrorException("Could not read file '" . $file . "'");
                    }
                }
            }
        }
    }
}