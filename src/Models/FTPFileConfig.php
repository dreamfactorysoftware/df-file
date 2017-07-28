<?php

namespace DreamFactory\Core\File\Models;

use DreamFactory\Core\File\Components\SupportsFiles;
use DreamFactory\Core\Models\BaseServiceConfigModel;

class FTPFileConfig extends BaseServiceConfigModel
{
    use SupportsFiles;

    /** @var string */
    protected $table = 'ftp_service_config';

    /** @var array */
    protected $encrypted = ['password'];

    /** @var array */
    protected $protected = ['password'];

    /** @var array */
    protected $fillable = [
        'service_id',
        'host',
        'port',
        'username',
        'password',
        'ssl',
        'passive',
        'timeout'
    ];

    /** @var array */
    protected $casts = [
        'service_id' => 'integer',
        'port'       => 'integer',
        'ssl'        => 'boolean',
        'passive'    => 'boolean',
        'timeout'    => 'integer'
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
            case 'host':
                $schema['description'] = 'FTP server host name or IP address.';
                break;
            case 'port':
                $schema['default'] = 21;
                $schema['description'] = 'FTP server port number. Default is 21.';
                break;
            case 'username':
                $schema['description'] = 'FTP server username.';
                break;
            case 'password':
                $schema['description'] = 'FTP server user password.';
                break;
            case 'ssl':
                $schema['label'] = 'SSL';
                $schema['default'] = false;
                $schema['description'] = 'Check to enable SSL.';
                break;
            case 'passive':
                $schema['default'] = true;
                $schema['description'] = 'Check to enable passive mode';
                break;
            case 'timeout':
                $schema['default'] = 90;
                $schema['description'] = 'Number of seconds before the connection will timeout. Default is 90 seconds.';
                break;
        }
    }
}