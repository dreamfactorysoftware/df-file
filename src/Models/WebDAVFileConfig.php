<?php

namespace DreamFactory\Core\File\Models;

use DreamFactory\Core\File\Components\SupportsFiles;
use DreamFactory\Core\Models\BaseServiceConfigModel;
use Sabre\DAV\Client;

class WebDAVFileConfig extends BaseServiceConfigModel
{
    use SupportsFiles;

    /** @var string */
    protected $table = 'webdav_service_config';

    /** @var array */
    protected $encrypted = ['password'];

    /** @var array */
    protected $protected = ['password'];

    /** @var array */
    protected $fillable = [
        'service_id',
        'base_uri',
        'username',
        'password',
        'auth_type',
        'encoding',
        'proxy'
    ];

    /** @var array */
    protected $casts = [
        'service_id' => 'integer',
        'auth_type'       => 'integer',
        'encoding'    => 'integer'
    ];

    /**
     * {@inheritdoc}
     */
    public static function getConfigSchema()
    {
        $out = (array)parent::getConfigSchema();
        $pathSchema = (array)FilePublicPath::getConfigSchema();
        $pathSchema[1]['label'] = 'Root folder';
        $pathSchema[1]['description'] = 'Enter a full path for a root folder.';

        return array_merge($out, $pathSchema);
    }

    /**
     * {@inheritdoc}
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'base_uri':
                $schema['description'] = 'WebDAV base uri';
                break;
                break;
            case 'username':
                $schema['description'] = 'WebDAV server username.';
                break;
            case 'password':
                $schema['description'] = 'WebDAV server user password.';
                break;
            case 'auth_type':
                $schema['type'] = 'picklist';
                $schema['values'] = [
                    ['label' => 'None', 'name' => null],
                    ['label' => 'Basic', 'name' => Client::AUTH_BASIC],
                    ['label' => 'Digest', 'name' => Client::AUTH_DIGEST],
                    ['label' => 'NTLM', 'name' => Client::AUTH_NTLM]
                ];
                $schema['description'] = 'Auth Type. Valid options are basic, digest, ntlm.';
                break;
            case 'encoding':
                $schema['type'] = 'picklist';
                $schema['values'] = [
                    ['label' => 'None', 'name' => null],
                    ['label' => 'Identity', 'name' => Client::ENCODING_IDENTITY],
                    ['label' => 'Deflate', 'name' => Client::ENCODING_DEFLATE],
                    ['label' => 'Gzip', 'name' => Client::ENCODING_GZIP]
                ];
                $schema['description'] = 'Encoding. Valid options are identity, deflate, gzip.';
                break;
            case 'proxy':
                $schema['description'] = 'Optional proxy server if any.';
                break;
        }
    }
}