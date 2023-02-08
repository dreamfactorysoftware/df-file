<?php

namespace DreamFactory\Core\File\Components;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;

class FTPFileSystem extends BaseFlysystem
{

    private MimeTypeDetector $mimeTypeDetector;

    public function __construct($config)
    {
        parent::__construct($config);
        $this->mimeTypeDetector = new ExtensionMimeTypeDetector();
    }

    /**
     * {@inheritdoc}
     */
    protected function setAdapter($config)
    {
        $this->adapter = new DfFtpAdapter($config);
    }

    /**
     * Override default implementation in order to skip file content reading. 
     * Detects mime type only by $path. Don't verify file existence.
     * @return string Detected mime type or 'text/plain' if type unknown
     */
    protected function detectMimeType(string $path) : string
    {
        return $this->mimeTypeDetector->detectMimeTypeFromPath($path) ?: 'text/plain';
    }
}