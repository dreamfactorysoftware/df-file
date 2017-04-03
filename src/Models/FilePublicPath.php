<?php
namespace DreamFactory\Core\File\Models;

use DreamFactory\Core\Models\BaseServiceConfigModel;

class FilePublicPath extends BaseServiceConfigModel
{
    protected $table = 'file_service_config';

    protected $fillable = ['service_id', 'public_path', 'container'];

    protected $casts = ['public_path' => 'array', 'service_id' => 'integer'];

    protected $rules = ['container' => 'required'];
    /**
     * {@inheritdoc}
     */
    public static function getConfig($id, $local_config = null, $protect = true)
    {
        if (null === $config = parent::getConfig($id, $local_config, $protect)) {
            $config = ['public_path' => [], 'container' => null, 'service_id' => $id];
        }

        return $config;
    }

    /**
     * @param array $schema
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'public_path':
                $schema['type'] = 'array';
                $schema['items'] = 'string';
                $schema['description'] = 'An array of paths to make public.' .
                    ' All folders and files under these paths will be available as public but read-only via the server\'s URL.';
                break;
            case 'container':
                $schema['type'] = 'text';
                $schema['description'] = 'Enter a Container (root directory) for your storage service.' .
                    ' It will be created if it does not exist already.';
                break;
        }
    }
}