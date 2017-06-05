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
                $schema['default'] = storage_path('app');
                $schema['description'] = 'Enter a full path for a root folder.';
                break;
            default:
                parent::prepareConfigSchemaField($schema);
                break;
        }
    }
}