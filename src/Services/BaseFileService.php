<?php

namespace DreamFactory\Core\File\Services;

use DreamFactory\Core\Contracts\FileServiceInterface;
use DreamFactory\Core\Enums\ApiOptions;
use DreamFactory\Core\Contracts\ServiceResponseInterface;
use DreamFactory\Core\Exceptions\NotFoundException;
use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\Utility\FileUtilities;
use DreamFactory\Core\Utility\ResourcesWrapper;
use DreamFactory\Core\Utility\ResponseFactory;
use DreamFactory\Core\Enums\Verbs;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class BaseFileService
 *
 * @package DreamFactory\Core\Services
 */
abstract class BaseFileService extends BaseRestService implements FileServiceInterface
{
    /**
     * @var \DreamFactory\Core\Contracts\FileSystemInterface
     */
    protected $driver = null;
    /**
     * @var string Storage container name
     */
    protected $container = null;
    /**
     * @var string Full folder path of the resource
     */
    protected $folderPath = null;
    /**
     * @var string Full file path of the resource
     */
    protected $filePath = null;
    /**
     * @var array Array of private path strings
     */
    public $publicPaths = [];

    //*************************************************************************
    //	Methods
    //*************************************************************************

    /**
     * @param array $settings
     */
    public function __construct($settings = [])
    {
        $settings = (array)$settings;
        $settings['verbAliases'] = [
            Verbs::PUT => Verbs::POST,
        ];
        parent::__construct($settings);

        $this->publicPaths = (array)Arr::get($this->config, 'public_path');
        $this->setDriver($this->config);
    }

    /**
     * @return \DreamFactory\Core\Contracts\FileSystemInterface
     */
    public function driver()
    {
        return $this->driver;
    }

    /**
     * @return string
     */
    public function getContainerId()
    {
        return $this->container;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @return string
     */
    public function getFolderPath()
    {
        return $this->folderPath;
    }

    public function getPublicPaths()
    {
        return $this->publicPaths;
    }

    public function isPublicPath($path)
    {
        if (in_array($path, $this->publicPaths)) {
            return true;
        }

        // Clean trailing slashes from paths
        array_walk($this->publicPaths, function (&$value){
            $value = rtrim($value, '/');
        });

        $directory = rtrim(substr($path, 0, strlen(substr($path, 0, strrpos($path, '/')))), '/');
        $pieces = explode("/", $directory);
        $dir = null;
        $allowed = false;

        foreach ($pieces as $p) {
            if (empty($dir)) {
                $dir = $p;
            } else {
                $dir .= "/" . $p;
            }

            if (in_array($dir, $this->publicPaths)) {
                $allowed = true;
                break;
            }
        }

        return $allowed;
    }

    public function folderExists($path)
    {
        return $this->driver->folderExists($this->container, $path);
    }

    public function getFolder($path, $include_files = true, $include_folders = true, $full_tree = false)
    {
        return $this->driver->getFolder($this->container, $path, $include_files, $include_folders, $full_tree);
    }

    public function getFolderProperties($path)
    {
        return $this->driver->getFolderProperties($this->container, $path);
    }

    public function createFolder($path, $properties = [])
    {
        $this->driver->createFolder($this->container, $path, $properties);
    }

    public function updateFolderProperties($path, $properties = [])
    {
        $this->driver->updateFolderProperties($this->container, $path, $properties);
    }

    public function copyFolder($dest_path, $src_container, $src_path, $check_exist = false)
    {
        $this->driver->copyFolder($this->container, $dest_path, $src_container, $src_path, $check_exist);
    }

    public function deleteFolders($folders, $root = '', $force = false)
    {
        return $this->driver->deleteFolders($this->container, $folders, $root, $force);
    }

    public function deleteFolder($path, $force = false, $content_only = false)
    {
        $this->driver->deleteFolder($this->container, $path, $force, $content_only);
    }

    public function fileExists($path)
    {
        return $this->driver->fileExists($this->container, $path);
    }

    public function getFileContent($path, $local_file = null, $content_as_base = true)
    {
        return $this->driver->getFileContent($this->container, $path, $local_file, $content_as_base);
    }

    public function getFileProperties($path, $include_content = false, $content_as_base = true)
    {
        return $this->driver->getFileProperties($this->container, $path, $include_content, $content_as_base);
    }

    public function updateFileProperties($path, $properties = [])
    {
        $this->driver->updateFileProperties($this->container, $path, $properties);
    }

    public function writeFile($path, $content, $content_is_base = true, $check_exist = false)
    {
        $this->driver->writeFile($this->container, $path, $content, $content_is_base, $check_exist);
    }

    public function moveFile($path, $local_path, $check_exist = false)
    {
        $this->driver->moveFile($this->container, $path, $local_path, $check_exist);
    }

    public function copyFile($dest_path, $sc_container, $src_path, $check_exist = false)
    {
        $this->driver->copyFile($this->container, $dest_path, $sc_container, $src_path, $check_exist);
    }

    public function deleteFile($path, $noCheck = false)
    {
        $this->driver->deleteFile($this->container, $path, $noCheck);
    }

    public function deleteFiles($files, $root = null)
    {
        return $this->driver->deleteFiles($this->container, $files, $root);
    }

    public function streamFile($path, $download = false)
    {
        $this->driver->streamFile($this->container, $path, $download);
    }

    public function getFolderAsZip($path, $zip = null, $zipFileName = null, $overwrite = false)
    {
        return $this->driver->getFolderAsZip($this->container, $path, $zip, $zipFileName, $overwrite);
    }

    public function extractZipFile($path, $zip, $clean = false, $drop_path = null)
    {
        $path = FileUtilities::fixFolderPath($path);

        return $this->driver->extractZipFile($this->container, $path, $zip, $clean, $drop_path);
    }

    /**
     * Sets the file system driver Local/S3/Azure/OStack...
     *
     * @param $config
     *
     * @return mixed
     */
    abstract protected function setDriver($config);

    /**
     * Apply the commonly used REST path members to the class.
     *
     * @param string $resourcePath
     *
     * @return $this
     */
    protected function setResourceMembers($resourcePath = null)
    {
        // File services need the trailing slash '/' for designating folders vs files
        // It is removed by the parent method
        $isFolder = (empty($resourcePath) ? false : ('/' === substr($resourcePath, -1)));
        parent::setResourceMembers($resourcePath);

        if (!empty($resourcePath)) {
            if ($isFolder) {
                $this->folderPath = $resourcePath;
            } else {
                $this->folderPath = dirname($resourcePath) . '/';
                $this->filePath = $resourcePath;
            }
        }

        return $this;
    }

    protected static function getResourceIdentifier()
    {
        return 'path';
    }

    /**
     * @param array $resources
     *
     * @return bool|mixed
     * @throws BadRequestException
     * @throws NotFoundException
     */
    protected function handleResource(array $resources)
    {
        //  Fall through is to process just like a no-resource request
        $resources = $this->getResourceHandlers();
        if ((false !== $resources) && !empty($this->resource)) {
            if (in_array($this->resource, $resources)) {
                return $this->processRequest();
            }
        }

        throw new NotFoundException("Resource '{$this->resource}' not found for service '{$this->name}'.");
    }

    public function getAccessList()
    {
        $list = parent::getAccessList();

        $result = $this->driver->getFolder($this->container, '', false, true, true);
        foreach (array_column($result, 'path') as $resource) {
            $list[] = $resource;
            $list[] = $resource . '*';
        }

        return $list;
    }

    protected function getEventName()
    {
        $suffix = '';
        if (!empty($this->filePath)) {
            $suffix = '.{file_path}';
        } elseif (!empty($this->folderPath)) {
            $suffix = '.{folder_path}';
        }

        return parent::getEventName() . $suffix;
    }

    protected function getEventResource()
    {
        if (!empty($this->filePath)) {
            return $this->filePath;
        } elseif (!empty($this->folderPath)) {
            return $this->folderPath;
        } else {
            return parent::getEventResource();
        }
    }

    /**
     * Searches for files/folders.
     *
     * @param string $search
     *
     * @return array
     */
    protected function doSearch($search)
    {
        $found = [];
        $result = $this->driver->getFolder(
            $this->container,
            $this->folderPath,
            $this->request->getParameterAsBool('include_files', true),
            $this->request->getParameterAsBool('include_folders', true),
            $this->request->getParameterAsBool('full_tree', false)
        );

        foreach ($result as $rs) {
            if (stripos(basename($rs['path']), $search) !== false) {
                $found[] = $rs;
            }
        }

        return $found;
    }

    /**
     * Handles GET actions.
     *
     * @throws \Exception
     * @return \DreamFactory\Core\Utility\ServiceResponse|StreamedResponse|array
     */
    protected function handleGET()
    {
        if (empty($this->folderPath) && empty($this->filePath) &&
            $this->request->getParameterAsBool(ApiOptions::AS_ACCESS_LIST)
        ) {
            return ResourcesWrapper::wrapResources($this->getAccessList());
        }

        if (empty($this->filePath)) {
            //Resource is the root/container or a folder
            if ($this->request->getParameterAsBool('zip')) {
                $zipFileName = '';
                if (empty($this->container) && empty($this->folderPath)) {
                    $zipFileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->getName() . '_' . time() . '.zip';
                }
                $zipFileName = $this->driver->getFolderAsZip($this->container, $this->folderPath, null, $zipFileName);
                $response = new StreamedResponse();
                $response->setCallback(function () use ($zipFileName){
                    $chunk = \Config::get('df.file_chunk_size');
                    FileUtilities::sendFile($zipFileName, true, $chunk);
                    unlink($zipFileName);
                });

                return $response;
            } elseif ($this->request->getParameterAsBool('include_properties')) {
                $result = $this->driver->getFolderProperties($this->container, $this->folderPath);
            } else {
                $search = $this->request->getParameter('search');
                if (!empty($search)) {
                    $result = $this->doSearch($search);
                } else {
                    $result = $this->driver->getFolder(
                        $this->container,
                        $this->folderPath,
                        $this->request->getParameterAsBool('include_files', true),
                        $this->request->getParameterAsBool('include_folders', true),
                        $this->request->getParameterAsBool('full_tree', false)
                    );
                }

                $asList = $this->request->getParameterAsBool(ApiOptions::AS_LIST);
                $idField = $this->request->getParameter(ApiOptions::ID_FIELD, static::getResourceIdentifier());
                $fields = $this->request->getParameter(ApiOptions::FIELDS, ApiOptions::FIELDS_ALL);

                $result = ResourcesWrapper::cleanResources($result, $asList, $idField, $fields, true);
            }
        } else {
            // Resource is a file
            // Check to see if file exists
            if (!$this->driver->fileExists($this->container, $this->filePath)) {
                throw new NotFoundException("The specified file '" . $this->filePath . "' was not found in storage.");
            }
            if ($this->request->getParameterAsBool('include_properties', false)) {
                // just properties of the file itself
                $content = $this->request->getParameterAsBool('content', false);
                $base64 = $this->request->getParameterAsBool('is_base64', true);
                $result = $this->driver->getFileProperties($this->container, $this->filePath, $content, $base64);
            } else {
                $download = $this->request->getParameterAsBool('download', false);
                // stream the file using StreamedResponse, exits processing
                $response = new StreamedResponse();
                $service = $this;
                $response->setCallback(function () use ($service, $download){
                    $service->streamFile($service->filePath, $download);
                });

                return $response;
            }
        }

        return $result;
    }

    /**
     * Handles POST actions.
     *
     * @return \DreamFactory\Core\Utility\ServiceResponse
     * @throws BadRequestException
     * @throws InternalServerErrorException
     * @throws \Exception
     */
    protected function handlePOST()
    {
        $extract = $this->request->getParameterAsBool('extract', false);
        $clean = $this->request->getParameterAsBool('clean', false);
        $checkExist = $this->request->getParameterAsBool('check_exist', false);

        if (empty($this->filePath)) {
            $fileNameHeader = str_to_utf8($this->request->getHeader('X-File-Name'));
            $folderNameHeader = str_to_utf8($this->request->getHeader('X-Folder-Name'));
            $fileUrl = filter_var($this->request->getParameter('url', ''), FILTER_SANITIZE_URL);

            if (!empty($fileNameHeader)) {
                // html5 single posting for file create
                $result = $this->handleFileContent(
                    $this->folderPath,
                    $fileNameHeader,
                    $this->request->getContent(),
                    $this->request->getContentType(),
                    $extract,
                    $clean,
                    $checkExist
                );
            } elseif (!empty($folderNameHeader)) {
                // html5 single posting for folder create
                $fullPathName = $this->folderPath . $folderNameHeader;
                $content = $this->request->getPayloadData();
                $this->driver->createFolder($this->container, $fullPathName, $content);
                $result = ['name' => $folderNameHeader, 'path' => $fullPathName];
            } elseif (!empty($fileUrl)) {
                // upload a file from a url, could be expandable zip
                $tmpName = null;
                $newFileName = $this->request->input('filename', '');
                try {
                    $tmpName = FileUtilities::importUrlFileToTemp($fileUrl);
                    $result = $this->handleFile(
                        $this->folderPath,
                        $newFileName,
                        $tmpName,
                        '',
                        $extract,
                        $clean,
                        $checkExist
                    );
                    @unlink($tmpName);
                } catch (\Exception $ex) {
                    if (!empty($tmpName)) {
                        @unlink($tmpName);
                    }
                    throw $ex;
                }
            } elseif (null !== $uploadedFiles = $this->request->getFile('files')) {
                if (Arr::isAssoc($uploadedFiles)) { //Single file upload
                    $uploadedFiles = [$uploadedFiles];
                }
                $result = $this->handleFolderContentFromFiles($uploadedFiles, $extract, $clean, $checkExist);
                $result = ResourcesWrapper::cleanResources($result);
            } else {
                // possibly xml or json post either of files or folders to create, copy or move
                if (!empty($data = ResourcesWrapper::unwrapResources($this->getPayloadData()))) {
                    $result = $this->handleFolderContentFromData($data, $extract, $clean, $checkExist);
                    $result = ResourcesWrapper::cleanResources($result);
                } else {
                    // create folder from resource path
                    $this->driver->createFolder($this->container, $this->folderPath);
                    $result = ['name' => basename($this->folderPath), 'path' => $this->folderPath];
                }
            }
        } else {
            // create the file
            // possible file handling parameters
            $name = basename($this->filePath);
            $path = (false !== strpos($this->filePath, '/')) ? dirname($this->filePath) : '';
            $files = $this->request->getFile('files');

            if (empty($files)) {
                // direct load from posted data as content
                // or possibly xml or json post of file properties create, copy or move
                $result = $this->handleFileContent(
                    $path,
                    $name,
                    $this->request->getContent(),
                    $this->request->getContentType(),
                    $extract,
                    $clean,
                    $checkExist
                );
            } else {
                if (Arr::isAssoc($files)) { // Single file upload
                    $files = [$files];
                }
                if (1 < count($files)) {
                    throw new BadRequestException("Multiple files uploaded to a single REST resource '$name'.");
                }
                $file = Arr::get($files, 0);
                if (empty($file)) {
                    throw new BadRequestException("No file uploaded to REST resource '$name'.");
                }
                $error = $file['error'];
                if (UPLOAD_ERR_OK == $error) {
                    $result = $this->handleFile(
                        $path,
                        $name,
                        $file["tmp_name"],
                        $file['type'],
                        $extract,
                        $clean,
                        $checkExist
                    );
                } else {
                    throw new InternalServerErrorException("Failed to upload file $name.\n$error");
                }
            }
        }

        return ResponseFactory::create($result, null, ServiceResponseInterface::HTTP_CREATED);
    }

    /**
     * Handles PATCH actions.
     *
     * @return \DreamFactory\Core\Utility\ServiceResponse
     */
    protected function handlePATCH()
    {
        $content = $this->getPayloadData();
        if (empty($this->folderPath)) {
            // update container properties
            $this->driver->updateContainerProperties($this->container, $content);
        } else {
            if (empty($this->filePath)) {
                // update folder properties
                $this->driver->updateFolderProperties($this->container, $this->folderPath, $content);
            } else {
                // update file properties?
                $this->driver->updateFileProperties($this->container, $this->filePath, $content);
            }
        }

        return ResponseFactory::create(['success' => true]);
    }

    /**
     * Handles DELETE actions.
     *
     * @return \DreamFactory\Core\Utility\ServiceResponse
     * @throws BadRequestException
     */
    protected function handleDELETE()
    {
        $force = $this->request->getParameterAsBool('force', false);
        $noCheck = $this->request->getParameterAsBool('no_check', false);
        $contentOnly = $this->request->getParameterAsBool('content_only', false);

        if (empty($this->folderPath)) {
            // delete just folders and files from the container
            if (!empty($content = ResourcesWrapper::unwrapResources($this->request->getPayloadData()))) {
                $result = $this->deleteFolderContent($content, '', $force);
            } else {
                throw new BadRequestException('No resources given for delete.');
            }
        } else {
            if (empty($this->filePath)) {
                // delete directory of files and the directory itself
                // multi-file or folder delete via post data
                if (!empty($content = ResourcesWrapper::unwrapResources($this->request->getPayloadData()))) {
                    $result = $this->deleteFolderContent($content, $this->folderPath, $force);
                } else {
                    $this->driver->deleteFolder($this->container, $this->folderPath, $force, $contentOnly);
                    $result = ['name' => basename($this->folderPath), 'path' => $this->folderPath];
                }
            } else {
                // delete file from permanent storage
                $this->driver->deleteFile($this->container, $this->filePath, $noCheck);
                $result = ['name' => basename($this->filePath), 'path' => $this->filePath];
            }
        }

        return ResponseFactory::create(ResourcesWrapper::cleanResources($result));
    }

    /**
     * Checks to see if the path has a trailing slash. This is used for
     * determining whether a path is a folder or file.
     *
     * @param string $path
     *
     * @return bool
     */
    protected function hasTrailingSlash($path)
    {
        return ('/' === substr($path, -1));
    }

    /**
     * @param        $dest_path
     * @param        $dest_name
     * @param        $content
     * @param string $contentType
     * @param bool   $extract
     * @param bool   $clean
     * @param bool   $check_exist
     *
     * @throws \Exception
     * @return array
     */
    protected function handleFileContent(
        $dest_path,
        $dest_name,
        $content,
        $contentType = '',
        $extract = false,
        $clean = false,
        $check_exist = false
    ){
        $ext = FileUtilities::getFileExtension($dest_name);
        if (empty($contentType)) {
            $contentType = FileUtilities::determineContentType($ext, $content);
        }
        if ((FileUtilities::isZipContent($contentType) || ('zip' === $ext)) && $extract) {
            // need to extract zip file and move contents to storage
            $tempDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $tmpName = $tempDir . $dest_name;
            file_put_contents($tmpName, $content);
            $zip = new \ZipArchive();
            $code = $zip->open($tmpName);
            if (true !== $code) {
                unlink($tmpName);

                throw new InternalServerErrorException('Error opening temporary zip file. code = ' . $code);
            }

            $results = $this->extractZipFile($dest_path, $zip, $clean);
            unlink($tmpName);

            return $results;
        } else {
            $fullPathName = FileUtilities::fixFolderPath($dest_path) . $dest_name;
            $this->driver->writeFile($this->container, $fullPathName, $content, false, $check_exist);

            return ['name' => $dest_name, 'path' => $fullPathName, 'type' => 'file'];
        }
    }

    /**
     * @param        $dest_path
     * @param        $dest_name
     * @param        $source_file
     * @param string $contentType
     * @param bool   $extract
     * @param bool   $clean
     * @param bool   $check_exist
     *
     * @throws \Exception
     * @return array
     */
    protected function handleFile(
        $dest_path,
        $dest_name,
        $source_file,
        $contentType = '',
        $extract = false,
        $clean = false,
        $check_exist = false
    ){
        $ext = FileUtilities::getFileExtension($source_file);
        if (empty($contentType)) {
            $contentType = FileUtilities::determineContentType($ext, '', $source_file);
        }
        if ((FileUtilities::isZipContent($contentType) || ('zip' === $ext)) && $extract) {
            // need to extract zip file and move contents to storage
            $zip = new \ZipArchive();
            if (true === $zip->open($source_file)) {
                return $this->extractZipFile($dest_path, $zip, $clean);
            } else {
                throw new InternalServerErrorException('Error opening temporary zip file.');
            }
        } else {
            $name = (empty($dest_name) ? basename($source_file) : $dest_name);
            $fullPathName = FileUtilities::fixFolderPath($dest_path) . $name;
            $this->driver->moveFile($this->container, $fullPathName, $source_file, $check_exist);

            return ['name' => $name, 'path' => $fullPathName, 'type' => 'file'];
        }
    }

    /**
     * @param array $files
     * @param bool  $extract
     * @param bool  $clean
     * @param bool  $checkExist
     *
     * @return array
     * @throws \Exception
     */
    protected function handleFolderContentFromFiles($files, $extract = false, $clean = false, $checkExist = false)
    {
        $out = [];
        $err = [];
        foreach ($files as $key => $file) {
            $name = FileUtilities::sanitizeFileName($file['name']);
            $error = $file['error'];
            if ($error == UPLOAD_ERR_OK) {
                $tmpName = $file['tmp_name'];

                // Get file's content type
                $contentType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $tmpName);

                if (empty($contentType)) {
                    // It is not safe to use content-type set by client.
                    // Therefore, only using content-type from client as a fallback.
                    $contentType = $file['type'];
                }

                $valid = FileUtilities::validateFileSignature($tmpName, $contentType);
                if (!$valid) {throw new InternalServerErrorException('File signature is not valid');}
                $tmp = $this->handleFile(
                    $this->folderPath,
                    $name,
                    $tmpName,
                    $contentType,
                    $extract,
                    $clean,
                    $checkExist
                );
                $out[$key] = $tmp;
            } else {
                $err[$name] = $error;
            }
        }
        if (!empty($err)) {
            $msg = 'Failed to upload the following files to folder ' . $this->folderPath . ': ' . implode(', ', array_keys($err)) .
                '. Error codes are: ' . implode(', ', array_values($err)) . '. See https://www.php.net/manual/en/features.file-upload.errors.php';
            throw new InternalServerErrorException($msg);
        }

        return $out;
    }

    /**
     * @param array $data
     * @param bool  $extract
     * @param bool  $clean
     * @param bool  $checkExist
     *
     * @return array
     */
    protected function handleFolderContentFromData(
        $data,
        /** @noinspection PhpUnusedParameterInspection */
        $extract = false,
        /** @noinspection PhpUnusedParameterInspection */
        $clean = false,
        /** @noinspection PhpUnusedParameterInspection */
        $checkExist = false
    ){
        $out = [];
        if (!empty($data) && !Arr::isAssoc($data)) {
            foreach ($data as $key => $resource) {
                switch (Arr::get($resource, 'type')) {
                    case 'folder':
                        $name = Arr::get($resource, 'name', '');
                        $srcPath = Arr::get($resource, 'source_path');
                        if (!empty($srcPath)) {
                            $srcContainer = Arr::get($resource, 'source_container', $this->container);
                            // copy or move
                            if (empty($name)) {
                                $name = FileUtilities::getNameFromPath($srcPath);
                            }
                            $fullPathName = $this->folderPath . $name . '/';
                            $out[$key] = ['name' => $name, 'path' => $fullPathName, 'type' => 'folder'];
                            try {
                                $this->driver->copyFolder($this->container, $fullPathName, $srcContainer, $srcPath,
                                    true);
                                $deleteSource = array_get_bool($resource, 'delete_source');
                                if ($deleteSource) {
                                    $this->driver->deleteFolder($this->container, $srcPath, true);
                                }
                            } catch (\Exception $ex) {
                                $out[$key]['error'] = ['message' => $ex->getMessage()];
                            }
                        } else {
                            $fullPathName = $this->folderPath . $name . '/';
                            $content = Arr::get($resource, 'content', '');
                            $isBase64 = array_get_bool($resource, 'is_base64');
                            if ($isBase64) {
                                $content = base64_decode($content);
                            }
                            $out[$key] = ['name' => $name, 'path' => $fullPathName, 'type' => 'folder'];
                            try {
                                $this->driver->createFolder($this->container, $fullPathName, $content);
                            } catch (\Exception $ex) {
                                $out[$key]['error'] = ['message' => $ex->getMessage()];
                            }
                        }
                        break;
                    case 'file':
                        $name = Arr::get($resource, 'name', '');
                        $srcPath = Arr::get($resource, 'source_path');
                        if (!empty($srcPath)) {
                            // copy or move
                            $srcContainer = Arr::get($resource, 'source_container', $this->container);
                            if (empty($name)) {
                                $name = FileUtilities::getNameFromPath($srcPath);
                            }
                            $fullPathName = $this->folderPath . $name;
                            $out[$key] = ['name' => $name, 'path' => $fullPathName, 'type' => 'file'];
                            try {
                                $this->driver->copyFile($this->container, $fullPathName, $srcContainer, $srcPath, true);
                                $deleteSource = array_get_bool($resource, 'delete_source');
                                if ($deleteSource) {
                                    $this->driver->deleteFile($this->container, $srcPath);
                                }
                            } catch (\Exception $ex) {
                                $out[$key]['error'] = ['message' => $ex->getMessage()];
                            }
                        } elseif (isset($resource['content'])) {
                            $fullPathName = $this->folderPath . $name;
                            $out[$key] = ['name' => $name, 'path' => $fullPathName, 'type' => 'file'];
                            $content = Arr::get($resource, 'content', '');
                            $isBase64 = array_get_bool($resource, 'is_base64');
                            if ($isBase64) {
                                $content = base64_decode($content);
                            }
                            try {
                                $this->driver->writeFile($this->container, $fullPathName, $content);
                            } catch (\Exception $ex) {
                                $out[$key]['error'] = ['message' => $ex->getMessage()];
                            }
                        }
                        break;
                }
            }
        }

        return $out;
    }

    /**
     * @param array  $data Array of sub-folder and file paths that are relative to the root folder
     * @param string $root root folder from which to delete
     * @param  bool  $force
     *
     * @return array
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     */
    protected function deleteFolderContent($data, $root = '', $force = false)
    {
        $root = FileUtilities::fixFolderPath($root);
        $out = [];
        if (!empty($data)) {
            foreach ($data as $key => $resource) {
                $path = Arr::get($resource, 'path');
                $name = Arr::get($resource, 'name');

                if (!empty($path)) {
                    $fullPath = $path;
                } else {
                    if (!empty($name)) {
                        $fullPath = $root . '/' . $name;
                    } else {
                        throw new BadRequestException('No path or name provided for resource.');
                    }
                }

                switch (Arr::get($resource, 'type')) {
                    case 'file':
                        $out[$key] = ['name' => $name, 'path' => $path, 'type' => 'file'];
                        try {
                            $this->driver->deleteFile($this->container, $fullPath);
                        } catch (\Exception $ex) {
                            $out[$key]['error'] = ['message' => $ex->getMessage()];
                        }
                        break;
                    case 'folder':
                        $out[$key] = ['name' => $name, 'path' => $path, 'type' => 'folder'];
                        try {
                            $this->driver->deleteFolder($this->container, $fullPath, $force);
                        } catch (\Exception $ex) {
                            $out[$key]['error'] = ['message' => $ex->getMessage()];
                        }
                        break;
                }
            }
        }

        return $out;
    }

    protected function getApiDocPaths()
    {
        $capitalized = camelize($this->name);

        return [
            '/'               => [
                'get'    => [
                    'summary'     => 'List all resources.',
                    'description' => 'List the resources (folders and files) available in this storage. ',
                    'operationId' => 'get' . $capitalized . 'Resources',
                    'parameters'  => [
                        ApiOptions::documentOption(ApiOptions::AS_LIST),
                        ApiOptions::documentOption(ApiOptions::AS_ACCESS_LIST),
                        [
                            'name'        => 'include_folders',
                            'description' => 'Include folders in the returned listing. Default is true.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'include_files',
                            'description' => 'Include files in the returned listing. Default is true.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'full_tree',
                            'description' => 'List the contents of all sub-folders as well.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'zip',
                            'description' => 'Return the content of the path as a zip file.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'search',
                            'description' => 'Search for file or folder by name.',
                            'schema'      => ['type' => 'string'],
                            'in'          => 'query',
                        ],
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/ResourceResponse']
                    ],
                ],
                'post'   => [
                    'summary'     => 'Create some folders and/or files.',
                    'description' => 'Post data as an array of folders and/or files. Folders are created if they do not exist',
                    'operationId' => 'create' . $capitalized . 'Content',
                    'parameters'  => [
                        [
                            'name'        => 'url',
                            'description' => 'The full URL of the file to upload.',
                            'schema'      => ['type' => 'string'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'extract',
                            'description' => 'Extract an uploaded zip file into the folder.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'clean',
                            'description' => 'Option when \'extract\' is true, clean the current folder before extracting files and folders.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'check_exist',
                            'description' => 'If true, the request fails when the file or folder to create already exists.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'X-File-Name',
                            'description' => 'File name to create.',
                            'schema'      => ['type' => 'string'],
                            'in'          => 'header',
                        ],
                        [
                            'name'        => 'X-Folder-Name',
                            'description' => 'Folder name to create.',
                            'schema'      => ['type' => 'string'],
                            'in'          => 'header',
                        ],
                    ],
                    'requestBody' => [
                        '$ref' => '#/components/requestBodies/FolderRequestPost'
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/FolderResponse']
                    ],
                ],
                'patch'  => [
                    'summary'     => 'Update container properties.',
                    'description' => 'Post body as an array of folder properties.',
                    'operationId' => 'update' . $capitalized . 'ContainerProperties',
                    'requestBody' => [
                        '$ref' => '#/components/requestBodies/SingleResourceRequest'
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/SuccessResponse']
                    ],
                ],
                'delete' => [
                    'summary'     => 'Delete some container contents.',
                    'description' =>
                        'Set \'content_only\' to true to delete the sub-folders and files contained, but not the container. ' .
                        'Set \'force\' to true to delete a non-empty folder. ' .
                        'Alternatively, to delete by a listing of sub-folders and files, ' .
                        'use the POST request with X-HTTP-METHOD = DELETE header and post listing.',
                    'operationId' => 'delete' . $capitalized . 'Content',
                    'parameters'  => [
                        [
                            'name'        => 'force',
                            'description' => 'Set to true to force delete on a non-empty folder.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'content_only',
                            'description' => 'Set to true to only delete the content of the container.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'no_check',
                            'description' => 'Set to true for not checking file existence.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                    ],
                    'requestBody' => [
                        '$ref' => '#/components/requestBodies/DeleteRequest'
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/FolderResponse']
                    ],
                ],
            ],
            '/{folder_path}/' => [
                'parameters' => [
                    [
                        'name'        => 'folder_path',
                        'description' => 'The path of the folder you want to retrieve. This can be a sub-folder, with each level separated by a \'/\'',
                        'schema'      => ['type' => 'string'],
                        'in'          => 'path',
                        'required'    => true,
                    ],
                ],
                'get'        => [
                    'summary'     => 'List the folder\'s content, including properties.',
                    'description' =>
                        'Use \'include_properties\' to get properties of the folder. ' .
                        'Use the \'include_folders\' and/or \'include_files\' to modify the listing.',
                    'operationId' => 'get' . $capitalized . 'Folder',
                    'parameters'  => [
                        [
                            'name'        => 'include_properties',
                            'description' => 'Return any properties of the folder in the response.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'include_folders',
                            'description' => 'Include folders in the returned listing. Default is true.',
                            'schema'      => ['type' => 'boolean', 'default' => true],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'include_files',
                            'description' => 'Include files in the returned listing. Default is true.',
                            'schema'      => ['type' => 'boolean', 'default' => true],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'full_tree',
                            'description' => 'List the contents of all sub-folders as well.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'zip',
                            'description' => 'Return the content of the folder as a zip file.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'search',
                            'description' => 'Search for file or folder by name.',
                            'schema'      => ['type' => 'string'],
                            'in'          => 'query',
                        ],
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/ResourceResponse']
                    ],
                ],
                'post'       => [
                    'summary'     => 'Create a folder and/or add content.',
                    'description' => 'Post data as an array of folders and/or files. Folders are created if they do not exist',
                    'operationId' => 'create' . $capitalized . 'Folder',
                    'parameters'  => [
                        [
                            'name'        => 'url',
                            'description' => 'The full URL of the file to upload.',
                            'schema'      => ['type' => 'string'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'extract',
                            'description' => 'Extract an uploaded zip file into the folder.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'clean',
                            'description' => 'Option when \'extract\' is true, clean the current folder before extracting files and folders.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'check_exist',
                            'description' => 'If true, the request fails when the file or folder to create already exists.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'X-File-Name',
                            'description' => 'File name to create.',
                            'schema'      => ['type' => 'string'],
                            'in'          => 'header',
                        ],
                        [
                            'name'        => 'X-Folder-Name',
                            'description' => 'Folder name to create.',
                            'schema'      => ['type' => 'string'],
                            'in'          => 'header',
                        ],
                    ],
                    'requestBody' => [
                        '$ref' => '#/components/requestBodies/FolderRequestPost'
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/FolderResponse']
                    ],
                ],
                'patch'      => [
                    'summary'     => 'Update folder properties.',
                    'description' => 'Post body as an array of folder properties.',
                    'operationId' => 'update' . $capitalized . 'FolderProperties',
                    'requestBody' => [
                        '$ref' => '#/components/requestBodies/SingleResourceRequest'
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/SuccessResponse']
                    ],
                ],
                'delete'     => [
                    'summary'     => 'Delete one folder and/or its contents.',
                    'description' =>
                        'Set \'content_only\' to true to delete the sub-folders and files contained, but not the folder. ' .
                        'Set \'force\' to true to delete a non-empty folder. ' .
                        'Alternatively, to delete by a listing of sub-folders and files, ' .
                        'use the POST request with X-HTTP-METHOD = DELETE header and post listing.',
                    'operationId' => 'delete' . $capitalized . 'Folder',
                    'parameters'  => [
                        [
                            'name'        => 'force',
                            'description' => 'Set to true to force delete on a non-empty folder.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'content_only',
                            'description' => 'Set to true to only delete the content of the folder.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'no_check',
                            'description' => 'Set to true for not checking file existence.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                    ],
                    'requestBody' => [
                        '$ref' => '#/components/requestBodies/DeleteRequest'
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/FolderResponse']
                    ],
                ],
            ],
            '/{file_path}'    => [
                'parameters' => [
                    [
                        'name'        => 'file_path',
                        'description' => 'Path and name of the file to retrieve.',
                        'schema'      => ['type' => 'string'],
                        'in'          => 'path',
                        'required'    => true,
                    ],
                ],
                'get'        => [
                    'summary'     => 'Download the file contents and/or its properties.',
                    'description' => 'By default, the file is streamed to the browser. ' .
                        'Use the \'download\' parameter to prompt for download. Set \'include_properties\' ' .
                        'to true to view file properties.',
                    'operationId' => 'get' . $capitalized . 'File',
                    'parameters'  => [
                        [
                            'name'        => 'include_properties',
                            'description' => 'Only show file properties.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query'
                        ],
                        [
                            'name'        => 'content',
                            'description' => 'Show content (base64 encoded) when displaying file properties.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query'
                        ],
                        [
                            'name'        => 'is_base64',
                            'description' => 'Set this to false to show file content in plain text. Otherwise shown in base64 encoded.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query'
                        ],
                        [
                            'name'        => 'download',
                            'description' => 'Prompt the user to download the file from the browser.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/FileResponse']
                    ],
                ],
                'post'       => [
                    'summary'     => 'Create a new file.',
                    'description' => 'Post body should be the contents of the file or an object with file properties.',
                    'operationId' => 'create' . $capitalized . 'File',
                    'parameters'  => [
                        [
                            'name'        => 'extract',
                            'description' => 'Extract an uploaded zip file into the folder.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'clean',
                            'description' => 'Option when \'extract\' is true, clean the current folder before extracting files and folders.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                        [
                            'name'        => 'check_exist',
                            'description' => 'If true, the request fails when the file or folder to create already exists.',
                            'schema'      => ['type' => 'boolean'],
                            'in'          => 'query',
                        ],
                    ],
                    'requestBody' => [
                        '$ref' => '#/components/requestBodies/FileRequest'
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/NewFileResponse']
                    ],
                ],
                'put'        => [
                    'summary'     => 'Update content of the file.',
                    'description' => 'Post body should be the contents of the file.',
                    'operationId' => 'replace' . $capitalized . 'File',
                    'requestBody' => [
                        '$ref' => '#/components/requestBodies/FileRequest'
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/NewFileResponse']
                    ],
                ],
                'patch'      => [
                    'summary'     => 'Update properties of the file.',
                    'description' => 'Post body should be an array of file properties.',
                    'operationId' => 'update' . $capitalized . 'FileProperties',
                    'requestBody' => [
                        '$ref' => '#/components/requestBodies/SingleResourceRequest'
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/SuccessResponse']
                    ],
                ],
                'delete'     => [
                    'summary'     => 'Delete one file.',
                    'description' => 'Careful, this removes the given file from the storage.',
                    'operationId' => 'delete' . $capitalized . 'File',
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/SingleResourceResponse']
                    ],
                ],
            ],
        ];
    }

    protected function getApiDocRequests()
    {
        return [
            'FolderRequest'         => [
                'description' => 'Resource List',
                'content'     => [
                    'application/json'    => [
                        'schema' => ['$ref' => '#/components/schemas/FolderRequest']
                    ],
                    'application/xml'     => [
                        'schema' => ['$ref' => '#/components/schemas/FolderRequest']
                    ],
                    'multipart/form-data' => [
                        'schema' => ['$ref' => '#/components/schemas/FileUploadRequest']
                    ],
                    'raw'                 => [
                        'schema' => ['type' => 'string']
                    ]
                ],
            ],
            'FolderRequestPost'     => [
                'description' => 'Resource List',
                'content'     => [
                    'application/json'    => [
                        'schema' => ['$ref' => '#/components/schemas/FolderRequestPost']
                    ],
                    'application/xml'     => [
                        'schema' => ['$ref' => '#/components/schemas/FolderRequestPost']
                    ],
                    'multipart/form-data' => [
                        'schema' => ['$ref' => '#/components/schemas/FileUploadRequest']
                    ],
                    'raw'                 => [
                        'schema' => ['type' => 'string']
                    ]
                ],
            ],
            'SingleResourceRequest' => [
                'description' => 'Resource List',
                'content'     => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/SingleResourceRequest']
                    ],
                    'application/xml'  => [
                        'schema' => ['$ref' => '#/components/schemas/SingleResourceRequest']
                    ],
                ],
            ],
            'FileRequest'           => [
                'description' => 'Resource List',
                'content'     => [
                    'multipart/form-data' => [
                        'schema' => ['$ref' => '#/components/schemas/FileUploadRequest']
                    ],
                    'raw'                 => [
                        'schema' => ['type' => 'string']
                    ]
                ],
            ],
            'DeleteRequest'         => [
                'description' => 'Resource List to delete',
                'content'     => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/DeleteRequest']
                    ],
                    'application/xml'  => [
                        'schema' => ['$ref' => '#/components/schemas/DeleteRequest']
                    ],
                ],
            ],
        ];
    }

    protected function getApiDocResponses()
    {
        return [
            'FolderResponse'         => [
                'description' => 'Resource List',
                'content'     => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/FolderResponse']
                    ],
                    'application/xml'  => [
                        'schema' => ['$ref' => '#/components/schemas/FolderResponse']
                    ],
                ],
            ],
            'SuccessResponse'        => [
                'description' => 'Operation successful',
                'content'     => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/SuccessResponse']
                    ],
                    'application/xml'  => [
                        'schema' => ['$ref' => '#/components/schemas/SuccessResponse']
                    ],
                ]
            ],
            'FileResponse'           => [
                'description' => 'Resource List',
                'content'     => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/FileResponse']
                    ],
                    'application/xml'  => [
                        'schema' => ['$ref' => '#/components/schemas/FileResponse']
                    ],
                ],
            ],
            'NewFileResponse'        => [
                'description' => 'Resource List',
                'content'     => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/NewFileResponse']
                    ],
                    'application/xml'  => [
                        'schema' => ['$ref' => '#/components/schemas/NewFileResponse']
                    ],
                ],
            ],
            'ResourceResponse'       => [
                'description' => 'Resource List',
                'content'     => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/ResourceResponse']
                    ],
                    'application/xml'  => [
                        'schema' => ['$ref' => '#/components/schemas/ResourceResponse']
                    ],
                ],
            ],
            'SingleResourceResponse' => [
                'description' => 'Resource List',
                'content'     => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/File']
                    ],
                    'application/xml'  => [
                        'schema' => ['$ref' => '#/components/schemas/File']
                    ],
                ],
            ],
        ];
    }

    protected function getApiDocSchemas()
    {
        $resourceWrapper = ResourcesWrapper::getWrapper();
        $commonFolder = [
            'name' => [
                'type'        => 'string',
                'description' => 'Identifier/Name for the folder, localized to requested resource.',
            ],
            'path' => [
                'type'        => 'string',
                'description' => 'Full path of the folder, from the service root.',
            ]
        ];

        $commonFile = [
            'name' => [
                'type'        => 'string',
                'description' => 'Identifier/Name for the file, localized to requested resource.',
            ],
            'path' => [
                'type'        => 'string',
                'description' => 'Full path of the file, from the service root.',
            ]
        ];

        $commonResource = array_merge($commonFolder, [
            'type'      => ['type' => 'string', 'description' => 'Type: file or folder'],
            'content'   => [
                'type'        => 'string',
                'description' => 'File content in plain text and/or base64 encoded. Applicable to file only.'
            ],
            'is_base64' => [
                'type'        => 'boolean',
                'description' => 'Set to true if content is base64 encoded. Applicable to file only.'
            ]
        ]);

        $models = [
            'FileRequest'           => [
                'type'       => 'object',
                'properties' => $commonFile,
            ],
            'FileResponse'          => [
                'type'       => 'object',
                'properties' => array_merge(
                    $commonFile,
                    [
                        'content_type'   => [
                            'type'        => 'string',
                            'description' => 'The media type of the content of the file.',
                        ],
                        'content_length' => [
                            'type'        => 'string',
                            'description' => 'Size of the file in bytes.',
                        ],
                        'last_modified'  => [
                            'type'        => 'string',
                            'description' => 'A GMT date timestamp of when the file was last modified.',
                        ],
                        'content'        => [
                            'type'        => 'string',
                            'description' => 'File content in plain text'
                        ]
                    ]
                ),
            ],
            'NewFileResponse'       => [
                'type'       => 'object',
                'properties' => array_merge(
                    $commonFolder,
                    [
                        'type' => ['type' => 'string', 'description' => 'Type: file or folder']
                    ]
                )
            ],
            'FolderRequest'         => [
                'type'       => 'object',
                'properties' => [
                    $resourceWrapper => [
                        'type'        => 'array',
                        'description' => 'An array of resources to operate on.',
                        'items'       => [
                            'type'       => 'object',
                            'properties' => $commonResource
                        ],
                    ],
                ],
            ],
            'FolderRequestPost'     => [
                'type'       => 'object',
                'properties' => [
                    $resourceWrapper => [
                        'type'        => 'array',
                        'description' => 'An array of resources to operate on.',
                        'items'       => [
                            'type'       => 'object',
                            'properties' => array_merge($commonResource, [
                                'source_path' => [
                                    'type'        => 'string',
                                    'description' => 'Use this to copy a file/folder from its source.'
                                ]
                            ])
                        ],
                    ],
                ],
            ],
            'SingleResourceRequest' => [
                'type'       => 'object',
                'properties' => $commonResource
            ],
            'FileUploadRequest'     => [
                'type'       => 'object',
                'properties' => [
                    'files' => [
                        'type'        => 'string',
                        'format'      => 'binary',
                        'description' => 'Use this for uploading binary file. Use files[] for multiple file upload.'
                    ]
                ]
            ],
            'SuccessResponse'       => [
                'type'       => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean']
                ]
            ],
            'FolderResponse'        => [
                'type'       => 'object',
                'properties' => [
                    $resourceWrapper => [
                        'type'        => 'array',
                        'description' => 'An array of resources',
                        'items'       => [
                            'type'       => 'object',
                            'properties' => array_merge(
                                $commonFolder,
                                [
                                    'type' => ['type' => 'string', 'description' => 'Type: file or folder']
                                ]
                            )
                        ]
                    ]
                ],
            ],
            'DeleteRequest'         => [
                'type'       => 'object',
                'properties' => [
                    $resourceWrapper => [
                        'type'        => 'array',
                        'description' => 'An array of resources',
                        'items'       => [
                            'type'       => 'object',
                            'properties' => array_merge(
                                $commonFolder,
                                [
                                    'type' => ['type' => 'string', 'description' => 'Type: file or folder']
                                ]
                            )
                        ]
                    ]
                ],
            ],
            'ResourceResponse'      => [
                'type'       => 'object',
                'properties' => [
                    $resourceWrapper => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => array_merge($commonFile, [
                                'type'           => ['type' => 'string', 'description' => 'Type: file or folder'],
                                'content_type'   => [
                                    'type'        => 'string',
                                    'description' => 'The media type of the content of the file. Applicable to file only.',
                                ],
                                'content_length' => [
                                    'type'        => 'string',
                                    'description' => 'Size of the file in bytes. Applicable to file only.',
                                ],
                                'last_modified'  => [
                                    'type'        => 'string',
                                    'description' => 'A GMT date timestamp of when the file was last modified. Applicable to file only.',
                                ],
                            ])
                        ]
                    ]
                ]
            ],
            'File'                  => [
                'type'       => 'object',
                'properties' => $commonFile,
            ],
            'Folder'                => [
                'type'       => 'object',
                'properties' => $commonFolder,
            ],
        ];

        return $models;
    }
}