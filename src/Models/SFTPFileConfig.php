<?php

namespace DreamFactory\Core\File\Models;

use DreamFactory\Core\File\Components\SupportsFiles;
use DreamFactory\Core\Models\BaseServiceConfigModel;

class SFTPFileConfig extends BaseServiceConfigModel
{
    use SupportsFiles;

    /** @var string */
    protected $table = 'sftp_service_config';

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
        'timeout',
        'host_fingerprint',
        'private_key'
    ];

    /** @var array */
    protected $casts = [
        'service_id' => 'integer',
        'port'       => 'integer',
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
                $schema['description'] = 'SFTP server host name or IP address.';
                break;
            case 'port':
                $schema['default'] = 22;
                $schema['description'] = 'SFTP server port number. Default is 22.';
                break;
            case 'username':
                $schema['description'] = 'SFTP server username.';
                break;
            case 'password':
                $schema['description'] = 'SFTP server user password.';
                break;
            case 'timeout':
                $schema['default'] = 90;
                $schema['description'] = 'Number of seconds before the connection will timeout. Default is 90 seconds.';
                break;
            case 'host_fingerprint':
                $schema['label'] = 'Host Finger Print';
                $schema['description'] = 'Finger print of the public key of the host you are connecting to.';
                break;
            case 'private_key':
                $schema['description'] = 'Private key (string or path to local file)';
                break;
        }
    }
}