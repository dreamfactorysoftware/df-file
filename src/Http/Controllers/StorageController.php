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

            $service = ServiceManager::getService(strtolower($storage));
            if (!($service instanceof FileServiceInterface)) {
                throw new BadRequestException('Service requested is not a file storage service.');
            }

            // Check for public paths here
            if (!$service->isPublicPath($path)) {
                throw new ForbiddenException('Access denied, please contact your system administrator.');
            }

            Log::info('[RESPONSE] File stream');

            $response = new StreamedResponse();
            $response->setCallback(function () use ($service, $path) {
                $service->streamFile($path);
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