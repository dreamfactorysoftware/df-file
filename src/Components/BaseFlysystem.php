<?php

namespace DreamFactory\Core\File\Components;

use DreamFactory\Core\Contracts\FileSystemInterface;
use DreamFactory\Core\Utility\FileUtilities;
use Log;
use DreamFactory\Core\Exceptions\ {
    InternalServerErrorException,
    NotImplementedException,
    BadRequestException,
    NotFoundException,
};
use League\Flysystem\ {
    DirectoryAttributes,
    StorageAttributes,
    FileAttributes,
    Config,
};
use League\Flysystem\ {
    UnableToCheckFileExistence,
    UnableToCheckExistence,
    UnableToCreateDirectory,
    UnableToDeleteDirectory, 
    FilesystemException,
    UnableToDeleteFile, 
    UnableToWriteFile,
    UnableToCopyFile,
    UnableToReadFile,
};

abstract class BaseFlysystem implements FileSystemInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var \League\Flysystem\FilesystemAdapter
     */
    protected \League\Flysystem\FilesystemAdapter $adapter;

    /**
     * @var \League\Flysystem\FilesystemOperator
     */
    private \League\Flysystem\FilesystemOperator $fileSystem;

    /**
     * FileSystem constructor.
     *
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->setAdapter($config);
        $this->fileSystem = new \League\Flysystem\Filesystem($this->adapter);
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
        try {
            return empty($path) || $this->fileSystem->directoryExists($path);
        } catch (FilesystemException | UnableToCheckExistence $exception) {
            throw new InternalServerErrorException('Failed to retrieve folder properties.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFolder($container, $path, $include_files = true, $include_folders = true, $full_tree = false)
    {
        if ($this->folderExists($container, $path)) {
            $path = rtrim($path, '/');
            $listing = $this->fileSystem->listContents($path, $full_tree)
                ->filter(fn ($item) => 
                    $item->isDir() && $include_folders ||
                    $item->isFile() && $include_files
                )
                ->map(fn ($item) => $this->mapAttributesToInfoArray($item, $path))
                ->toArray();
            return $listing;
        } else {
            throw new NotFoundException("Folder '$path' does not exist in storage.");
        }
    }

    /**
     * Create an array with metadata for provided file or folder
     * 
     * @param \League\Flysystem\StorageAttributes $item File or folder to retrieve metadata from
     * @param string $parentFolder Base path to exclude from item name
     * 
     * @return array
     */
    protected function mapAttributesToInfoArray(StorageAttributes $item, string $parentFolder): array
    {
        $info = ['path' => $item->path()];
        $this->normalizeName($info, $parentFolder);
        if ($this->includeLastModified($item)) {
            $info['last_modified'] = $this->lastModified($item);
        }

        if ($item instanceof \League\Flysystem\FileAttributes) {
            $info['type'] = 'file';
            $info['content_type'] = $this->mimeType($item);
            $info['content_length'] = $item->fileSize();
        } elseif ($item instanceof \League\Flysystem\DirectoryAttributes) {
            $info['type'] = 'folder';
            $info['path'] = FileUtilities::fixFolderPath($info['path']);
        }

        return $info;
    }

    /**
     * Exclude base path from item name. Modifies input `$info` array
     * 
     * @param array &$info Item metadata array with `name` and `path` keys
     * @param string $localizer Base path to exclude
     * 
     * @return void
     */
    private function normalizeName(array & $info, string $localizer)
    {
        $path = $info['path'];
        $info['name'] = trim(substr($path, strlen($localizer)), '/');
        if (empty($info['name'])) {
            $info['name'] = basename($path);
        }
    }

    /**
     * Determine whether the entity includes lastModified metadata
     * 
     * @param \League\Flysystem\StorageAttributes $item The entity from which to read metadata
     * 
     * @return bool
     */
    protected function includeLastModified(StorageAttributes $item): bool
    {
        return !empty($item->lastModified());
    }

    /**
     * Get lastModified file metadata in GMT time zone
     * 
     * @param \League\Flysystem\StorageAttributes $item The entity from which to read metadata
     * 
     * @return string
     */
    protected function lastModified(StorageAttributes $item): string
    {
        return gmdate('D, d M Y H:i:s \G\M\T', $item->lastModified() ?: 0);
    }

    /**
     * Get file MIME type
     * 
     * @param \League\Flysystem\FileAttributes $file The file from which to read metadata
     * 
     * @return string
     */
    protected function mimeType(FileAttributes $file) : string
    {
        return $file->mimeType() ?: $this->detectMimeType($file->path());
    }

    /**
     * Determine the MIME type of an entity
     * 
     * @param string $path 
     * 
     * @return string
     */
    protected function detectMimeType(string $path) : string
    {
        return $this->fileSystem->mimeType($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getFolderProperties($container, $path)
    {
        $path = rtrim($path, '/');
        if ($this->folderExists($container, $path)) {
            $props = $this->mapAttributesToInfoArray(
                $this->getCommonMetadata($path),
                $path,
            );
            unset($props['type']);
            return $props;
        } else {
            throw new NotFoundException("Specified folder '" . $path . "' not found.");
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

        try {
            $this->adapter->createDirectory($path, new Config());
        } catch (FilesystemException | UnableToCreateDirectory $exception) {
            throw new InternalServerErrorException("Failed to create folder '" . $path . "'.");
        }
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
        $meta = $this->getCommonMetadata($src);
        $this->copyTreeRecursively($meta, $dst);    
    }

    /**
     * @param StorageAttributes $src
     * @param string            $dst
     */
    protected function copyTreeRecursively(StorageAttributes $src, string $dst)
    {
        if ($src->isFile()) {
            $this->fileSystem->copy($src->path(), $dst);
        } else {
            $relevantRoot = FileUtilities::fixFolderPath($dst) . basename($src->path()); 
            $this->fileSystem->createDirectory($relevantRoot);
            $files = $this->fileSystem->listContents($src->path(), false);
            foreach ($files as $file) {
                $this->copyTreeRecursively($file, $relevantRoot . basename($file->path()));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFolder($container, $path, $force = false, $content_only = false)
    {
        if (!$this->folderExists($container, $path)) {
            throw new NotFoundException("Folder '" . $path . "' does not exist.");
        }
        $path = rtrim($path, '/');

        if ($force) {
            $this->deleteTree($path, !$content_only);
        } else {
            try {
                $this->adapter->deleteDirectory($path);
            } catch (FilesystemException | UnableToDeleteDirectory $exception) {
                throw new InternalServerErrorException("Directory not empty, can not delete without force option.");
            }
        }
    }

    /**
     * @param string $path
     * @param bool   $delete_self
     */
    public function deleteTree($path, $delete_self = true)
    {
        $path = rtrim($path, '/');
        $meta = $this->getCommonMetadata($path);
        
        if ($meta->isDir()) {
            $this->fileSystem->deleteDirectory($path);
            if ( ! $delete_self) {
                $this->fileSystem->createDirectory($path);
            }
        } else {
            $this->deleteFile(null, $path, true);
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
        try {
            return $this->adapter->fileExists($path);
        } catch (FilesystemException | UnableToCheckFileExistence $exception) {
            throw new InternalServerErrorException('Failed to retrieve file properties.');
        }
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
        $fileAttributes = $this->getFileAttributes($path);
        $meta = $this->mapAttributesToInfoArray($fileAttributes, $path);

        if ($include_content) {
            try {
                $contents = $this->adapter->read($path);
            } catch (FilesystemException | UnableToReadFile $exception) {
                throw new InternalServerErrorException('Failed to retrieve file properties.');
            }
            if ($content_as_base) {
                $contents = base64_encode($contents);
            }
            $meta['content'] = $contents;
        }

        unset($meta['type']);

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function streamFile($container, $path, $download = false)
    {
        if (empty($path)) {
            Log::warning('Failed to stream file: ' . $path);
            $statusHeader = 'HTTP/1.1 404';
            header($statusHeader);
            header('Content-Type: text/html');
            echo 'The specified file ' . $path . ' does not exist.';
            return;
        }

        try {
            $fileAttributes = $this->getFileAttributes($path);    

            $fileName = basename($path);
            $ext = FileUtilities::getFileExtension($fileName);
            $disposition = ($download) ? 'attachment; filename="' . $fileName . '";' : 'inline';

            header('Cache-Control: no-cache, private');
            header('Last-Modified: ' . $this->lastModified($fileAttributes));
            header('Content-Type: ' . $this->mimeType($fileAttributes));
            header('Content-Length:' . $fileAttributes->fileSize());
            if ($download || 'html' !== $ext) {
                header('Content-Disposition: ' . $disposition);
            }
            
            if ($this->notModified($fileAttributes)) {
                header('HTTP/1.1 304 Not Modified');
                return;
            }
            
            $chunk = \Config::get('df.file_chunk_size');
            $stream = $this->fileSystem->readStream($path);
            if (empty($chunk)) {
                print(fread($stream, $fileAttributes->fileSize()));
            } else {
                while (!feof($stream) and (connection_status() == 0)) {
                    print(fread($stream, $chunk));
                    flush();
                }
            }
        } catch (NotFoundException $ex) {
            header('HTTP/1.1 404 Not Found');
            echo 'Could not open the specified file ' . $path;
            echo $ex->getMessage();
        } catch (\Exception $ex) {
            header('HTTP/1.1 500 Internal Server Error');
            \Log::error('Failed to stream file: ' . $path);
            \Log::error($ex->getMessage());
            \Log::error($ex->getTraceAsString());
        }
    }

    /**
     * Checks if the file has not changed from the last request
     * 
     * @param FileAttributes $file
     *
     * @return bool
     */
    private function notModified(FileAttributes $file) : bool
    {
        $timestamp = $file->lastModified();
        $ifModifiedSince = \Illuminate\Support\Arr::get($_SERVER, 'HTTP_IF_MODIFIED_SINCE');
        return (
            !empty($timestamp) && 
            !empty($ifModifiedSince) && 
            strtotime($ifModifiedSince) === $timestamp
        );
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

        try {
            $this->adapter->write($path, $content, new Config());
        } catch (FilesystemException | UnableToWriteFile $exception) {
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
        try {
            $this->adapter->copy($src_path, $dest_path, new Config());
        } catch (FilesystemException | UnableToCopyFile $exception) {
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
        try {
            $this->adapter->delete($path);
        } catch (FilesystemException | UnableToDeleteFile $exception) {
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
     * @param string      $path
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     * @throws \Exception
     */
    public function addTreeToZip($zip, $path)
    {
        $path = rtrim($path, '/');
        if ($this->folderExists('', $path)) {
            $files = $this->fileSystem->listContents($path);
            $isEmpty = true;
            
            foreach ($files as $file) {
                $isEmpty = false;
                if ($file->isDir()) {
                    static::addTreeToZip($zip, $file->path());
                } elseif ($file->isFile()) {
                    $newPath = str_replace(DIRECTORY_SEPARATOR, '/', $file->path());
                    $content = $this->fileSystem->read($newPath);                    
                    if (!$zip->addFromString($file->path(), $content)) {
                        throw new \Exception("Can not include file '$newPath' in zip file.");
                    }
                }
            }
            if ($isEmpty) {
                $newPath = str_replace(DIRECTORY_SEPARATOR, '/', $path);
                if (!$zip->addEmptyDir($newPath)) {
                    throw new \Exception("Can not include folder '$newPath' in zip file.");
                }
            }
        }
    }

    /**
     * @param string $path
     *
     * @return StorageAttributes
     * @throws NotFoundException
     */
    protected function getCommonMetadata(string $path): StorageAttributes
    {
        if ($this->fileExists(null, $path)) {
            return new FileAttributes($path);
        } elseif ($this->folderExists(null, $path)) {
            return new DirectoryAttributes($path);
        } else {
            throw new NotFoundException("Invalid data provided. Specified path '" . $path . "' does not exist.");
        }
    }

    /**
     * @param string $path
     *
     * @return FileAttributes
     * @throws NotFoundException
     */
    protected function getFileAttributes(string $path): FileAttributes
    {
        if (!$this->fileSystem->fileExists($path)) {
            throw new NotFoundException("Specified file '" . $path . "' does not exist.");
        }
        
        return new FileAttributes(
            $path, 
            $this->fileSystem->fileSize($path),
            null,
            $this->fileSystem->lastModified($path),
            $this->detectMimeType($path),
        );
    }
}