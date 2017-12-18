<?php

namespace DreamFactory\Core\File\Http\Controllers;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\ForbiddenException;
use DreamFactory\Core\Components\DfResponse;
use DreamFactory\Core\Contracts\FileServiceInterface;
use DreamFactory\Core\Http\Controllers\Controller;
use Log;
use ServiceManager;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageController extends Controller
{
    public function streamFile($storage, $path)
    {
        try {
            Log::info('[REQUEST] Storage', [
                'Method'  => 'GET',
                'Service' => $storage,
                'Path'    => $path
            ]);

            $storage = strtolower($storage);

            /** @type FileServiceInterface $service */
            $service = ServiceManager::getService($storage);
            if (!($service instanceof FileServiceInterface)) {
                throw new BadRequestException('Service requested is not a file storage service.');
            }

            // Check for private paths here.
            $publicPaths = $service->getPublicPaths();

            // Clean trailing slashes from paths
            array_walk($publicPaths, function (&$value) {
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

                if (in_array($dir, $publicPaths)) {
                    $allowed = true;
                    break;
                }
            }

            if (!$allowed) {
                throw new ForbiddenException('Access denied, please contact your system administrator.');
            }
            Log::info('[RESPONSE] File stream');

            $response = new StreamedResponse();
            $response->setCallback(function () use ($service, $path) {
                $service->streamFile($service->getContainerId(), $path);
            });

            return $response;
        } catch (\Exception $e) {
            $content = $e->getMessage();
            if (empty($status = $e->getCode())) {
                $status = 500;
            }

            $contentType = 'text/html';
            Log::info('[RESPONSE]', ['Status Code' => $status, 'Content-Type' => $contentType]);

            return DfResponse::create($content, $status, ["Content-Type" => $contentType]);
        }
    }
}