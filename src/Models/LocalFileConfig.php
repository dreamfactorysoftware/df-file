<?php

namespace DreamFactory\Core\File\Models;

class LocalFileConfig extends FilePublicPath
{
    /**
     * @param array $schema
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        switch ($schema['name']) {
            case 'container':
                $schema['label'] = 'Root Folder';
                $schema['description'] = 'Enter a full folder path from your system root, ' .
                    'or a path relative to the configured storage folder for this installation, i.e. ' . storage_path() .
                    '. This path must be readable and writable by the web server.';
                break;
            default:
                parent::prepareConfigSchemaField($schema);
                break;
        }
    }
}